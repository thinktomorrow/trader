<?php

$dsn = 'mysql:host=127.0.0.1;dbname=trader-dev';
$pdo = new \PDO($dsn, 'root', '', [
    \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
]);

//inserting node
function insert($pdo, $parentId, $nodeName)
{
    $sql = "CALL r_tree_traversal('insert', NULL, $parentId);";
    $prep = $pdo->prepare($sql);
    $prep->execute();
    $newNodeId = (int) $prep->fetchColumn();

    $sql = "INSERT INTO tree_content (node_id, name) VALUES (?,?)";
    $prep = $pdo->prepare($sql);
    $prep->execute([$newNodeId, $nodeName]);
}

//insert($pdo, 4, 'boeken');
//insert($pdo, 4, 'kaften');
//insert($pdo, 4, 'schriften');


$sql = "CALL r_return_tree(NULL, 'en');";
$prep = $pdo->prepare($sql);
$prep->execute();
$selectOptions = $prep->fetchAll(PDO::FETCH_OBJ);
?>
<pre>
<?php
//current tree structure
$tree = '';
foreach ($selectOptions as $key => $row) {
    $tree .= sprintf('%s' . PHP_EOL, $row->name);
}
echo rtrim($tree, PHP_EOL);
?>
</pre>

