<?php
require_once 'DAO.php';

class Goods {
    public string $goodsName;
    public int $price;
    public int $total;
    public string $buytime;
}

class GoodsDAO {
    public function insert(Goods $goods) {
        $dbh = DAO::get_db_connect();
        $sql = "INSERT INTO goods (goodsName, price, total, buytime) 
                VALUES (:goodsName, :price, :total, :buytime)";
        $stmt = $dbh->prepare($sql);
        
        // 使用数组传递参数
        $params = [
            ':goodsName' => $goods->goodsName,
            ':price' => $goods->price,
            ':total' => $goods->total,
            ':buytime' => $goods->buytime
        ];
        
        return $stmt->execute($params);
    }
    public function select() {
        $dbh = DAO::get_db_connect();
        $sql = "SELECT * FROM goods";
        $stmt = $dbh->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}