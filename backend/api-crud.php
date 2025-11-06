<?php
declare(strict_types=1);
ini_set('display_errors','1');
ini_set('display_startup_errors','1');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=canteen_new_db','root','',[
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error'=>'DB connection failed: '.$e->getMessage()]);
    exit;
}

function send($data,$code=200){ http_response_code($code); echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT); exit; }
$path = $_SERVER['REQUEST_URI'];
$pos = strpos($path,'api-crud.php');
$after = $pos !== false ? substr($path,$pos+strlen('api-crud.php')) : ($_SERVER['PATH_INFO'] ?? '/');
$parts = array_values(array_filter(explode('/',trim($after,'/'))));
$table = $parts[0] ?? null;
$id = isset($parts[1]) ? (int)$parts[1] : null;

$allowed = ['menu_items','orders','order_items','users','roles','ingredients','suppliers','purchases','payments','purchase_items'];

if (!$table) send(['app'=>'canteen-backend','tables'=>$allowed]);
if (!in_array($table,$allowed)) send(['error'=>'table not allowed','table'=>$table],404);

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET' && $id === null) {
        $stmt = $pdo->query("SELECT * FROM `$table` LIMIT 100");
        $rows = $stmt->fetchAll();
        send($rows);
    }
    if ($method === 'GET' && $id !== null) {
        $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE id=:id");
        $stmt->execute([':id'=>$id]);
        $row = $stmt->fetch();
        if (!$row) send(['error'=>'Not found'],404);
        send($row);
    }
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'),true);
        if (!is_array($data)) send(['error'=>'Invalid JSON'],400);
        $cols = array_keys($data);
        $ph = array_map(fn($c)=>":$c",$cols);
        $sql = "INSERT INTO `$table` (`".implode('`,`',$cols)."`) VALUES (".implode(',',$ph).")";
        $stmt = $pdo->prepare($sql);
        foreach($data as $k=>$v) $stmt->bindValue(":$k",$v);
        $stmt->execute();
        send(['id'=> (int)$pdo->lastInsertId()],201);
    }
    if (in_array($method,['PATCH','PUT']) && $id !== null) {
        $data = json_decode(file_get_contents('php://input'),true);
        if (!is_array($data)) send(['error'=>'Invalid JSON'],400);
        $sets = array_map(fn($c)=>"`$c`=:$c",array_keys($data));
        $sql = "UPDATE `$table` SET ".implode(',',$sets)." WHERE id=:id";
        $stmt = $pdo->prepare($sql);
        foreach($data as $k=>$v) $stmt->bindValue(":$k",$v);
        $stmt->bindValue(':id',$id);
        $stmt->execute();
        send(['updated'=>$stmt->rowCount()]);
    }
    if ($method === 'DELETE' && $id !== null) {
        $stmt = $pdo->prepare("DELETE FROM `$table` WHERE id=:id");
        $stmt->execute([':id'=>$id]);
        send(['deleted'=>$stmt->rowCount()]);
    }
    send(['error'=>'Unsupported operation'],400);
} catch (Throwable $e){
    send(['error'=>$e->getMessage()],500);
}
