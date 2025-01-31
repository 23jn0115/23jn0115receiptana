<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>レシートOCRアップロード</title>
    <!-- Bootstrap 5.3 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoXNz6zqU9DpDL3l8GSXUsI5IMLnwt7CO7flgK7+hF6J51y" crossorigin="anonymous"></script>
</head>
<body>
    <div class="container d-flex justify-content-center align-items-start min-vh-100" style="margin-top: 8rem;">
        <div class="card shadow-sm p-4" style="width: 100%; max-width:600px;">
            <h1 class="text-center mb-4">レシートOCRアップロード</h1>
            <form action="process.php" method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="receipt" class="form-label">レシート画像を選択してください（複数選択可能）:</label>
                    <input type="file" name="receipt[]" id="receipts" class="form-control" multiple accept="image/*" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">アップロード</button>
            </form>
        </div>
    </div>
</body>
</html>
