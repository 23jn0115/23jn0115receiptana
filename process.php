<?php
// 添加配置文件
require_once 'DAO.php';
require_once 'goodsDAO.php';

header("Content-Type: text/html; charset=utf-8");

$uploadDir = "images/";  // 存储上传图片的文件夹
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);  // 创建目录
}

$logFilePath = "ocr.log";
$csvFilePath = "receipt_data.csv";
$extractData = []; // 改为数组，每个元素存储一张发票的数据



// 检查是否有文件上传
if (!empty($_FILES['receipt']['name'][0])) {
    foreach ($_FILES['receipt']['name'] as $index => $fileName) {
        $tmpName = $_FILES['receipt']['tmp_name'][$index];
        $targetPath = $uploadDir . basename($fileName);

        // 将文件移动到指定目录
        if (move_uploaded_file($tmpName, $targetPath)) {
            // 调用 Python 脚本进行 OCR 处理
            $key = "AojCVksWoad1N2Lpq3rDBkf4Hc0hyDTATNzwBq7IXp5zVOQgzsFdJQQJ99BAACi0881XJ3w3AAALACOGYBHb";
            $endpoint = "https://jn0117receiptocr.cognitiveservices.azure.com/";

            // 运行 Python 脚本
            $command = "python ocr.py " . escapeshellarg($key) . " " . escapeshellarg($endpoint) . " " . escapeshellarg($targetPath) . " 2>&1";
            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);

            // 写入日志
            file_put_contents($logFilePath, date('Y-m-d H:i:s') . "\n", FILE_APPEND);
            file_put_contents($logFilePath, "Output: " . implode("\n", $output) . "\n\n", FILE_APPEND);

            // 处理 Python 返回的结果
            if ($returnVar === 0) {
                $jsonString = trim($output[count($output) - 1]);
                $result = json_decode($jsonString, true);
                if ($result) {
                    // 将每张发票的数据作为独立的元素存储
                    $extractData[] = $result;
                    
                    try {
                        // 创建 GoodsDAO 实例
                        $goodsDAO = new GoodsDAO();
                        
                        // 插入商品数据
                        foreach ($result["items"] as $item) {
                            $goods = new Goods();
                            $goods->goodsName = $item["description"];
                            $goods->price = $item["totalPrice"];
                            $goods->total = $result["total"];
                            $goods->buytime = $result["date"];
                            
                            // 使用 DAO 插入数据
                            $goodsDAO->insert($goods);
                        }
                    } catch (PDOException $e) {
                        file_put_contents($logFilePath, "Database error: " . $e->getMessage() . "\n", FILE_APPEND);
                        echo "<div class='alert alert-danger'>データベースエラーが発生しました。</div>";
                    }
                }
            }
        } else {
            echo "<p>Failed to upload $fileName.</p>";
        }
    }

    // 生成 CSV 文件
    $fp = fopen($csvFilePath, "w");
    fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // 为每张发票写入数据
    foreach ($extractData as $receipt) {
        fputcsv($fp, ["店舗名", $receipt["merchant"]]);
        fputcsv($fp, ["商品名", "価格"]);
        foreach ($receipt["items"] as $item) {
            fputcsv($fp, [$item["description"], $item["totalPrice"]]);
        }
        fputcsv($fp, ["合計", $receipt["total"]]); // 直接使用OCR识别的total值
        fputcsv($fp, []); // 空行分隔不同发票
    }
    
    fclose($fp);

} else {
    echo "<p>No file uploaded or upload error occurred.</p>";
}

// 在生成 CSV 之前添加搜索函数
function searchItems($receipts, $keyword) {
    $results = [];
    foreach ($receipts as $receiptIndex => $receipt) {
        foreach ($receipt["items"] as $item) {
            if (mb_stripos($item["description"], $keyword) !== false) {
                $results[] = [
                    "receipt_no" => $receiptIndex + 1,
                    "merchant" => $receipt["merchant"],
                    "date" => $receipt["date"] ?? "",
                    "time" => $receipt["time"] ?? "",
                    "description" => $item["description"],
                    "price" => $item["totalPrice"],
                    "quantity" => $item["quantity"] ?? 1,
                    "unit_price" => $item["unitPrice"] ?? $item["totalPrice"]
                ];
            }
        }
    }
    return $results;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt Data</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .receipt-card {
            margin-bottom: 30px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .card-header {
            background-color: #6c757d !important; /* 更改为更柔和的灰色 */
        }
        .download-buttons {
            margin: 20px 0;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center mb-4">全てのレシート</h2>
        
        <div class="download-buttons">
            <a href="receipt_data.csv" class="btn btn-success" download>
                <i class="bi bi-download"></i> CSVをダウンロード
            </a>
            <a href="ocr.log" class="btn btn-info" download>
                <i class="bi bi-download"></i> ログをダウンロード
            </a>
        </div>
        
        <?php foreach ($extractData as $index => $receipt): ?>
            <div class="card receipt-card">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">レシート <?php echo $index + 1; ?>: <?php echo htmlspecialchars($receipt["merchant"]); ?></h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>商品名</th>
                                    <th class="text-end">価格</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($receipt["items"] as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item["description"] ?? 'N/A'); ?></td>
                                        <td class="text-end"><?php 
                                            $price = $item["totalPrice"] ?? 'N/A';
                                            echo htmlspecialchars($price);
                                            if ($price !== 'N/A') echo '円';
                                        ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-primary">
                                    <td><strong>合計</strong></td>
                                    <td class="text-end"><strong><?php 
                                        echo htmlspecialchars($receipt["total"]) . '円';
                                    ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>