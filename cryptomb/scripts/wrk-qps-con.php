<?php
//wrk benchmark, short connection and close reused-session
function testTp($type, $conn, $params) {

    $mode = $params['m'];
    $wrkStr = "wrk -t8 -c{$conn} -d50";
    $httpUrl = "http://172.16.243.126";
    $httpsUrl = "https://172.16.243.126";

    if ($type == "httpsKeepAlive") {
        $wrkCmd = "{$wrkStr} --latency {$httpsUrl}";
    }
    elseif ($type == "httpsConnClose") {
        $wrkCmd = "{$wrkStr} -H 'Connection: Close' --latency {$httpsUrl}";
    }
    elseif ($type == "httpsSesionClose") {
        $wrkCmd = "{$wrkStr} -H 'Connection: Close' -m0 --latency {$httpsUrl}";
    }

    $wrkPath = "./../wrk-supp-result/{$mode}/{$type}/wrk";
    if(!is_dir($wrkPath)) {
        mkdir($wrkPath, 0777, true);
    }
    foreach (range(1, 3) as $index) {
        $wrkFile = "{$wrkPath}/latency_t1_c{$conn}_40s_{$index}";
        echo $wrkFile, ": start to exec ",$wrkCmd,"\n";
        $rawCmd = "{$wrkCmd} > {$wrkFile}";
        exec($rawCmd);
        sleep(10);
    }
}

$cType = array('httpsSesionClose');
$cConn = array('128', '256', '384', '512');

//m:mode
$param_arr = getopt('m:');
if (empty($param_arr)) {
    echo "usage: php wrk-qps-conn.php -m cryptomb\n";
    exit;
}
print_r($param_arr);

foreach ($cType as $t) {
    foreach ($cConn as $c) {
        echo "--------------------------------\n";
        echo "--start benchmark: type: {$t}; conn: {$c}--\n";
        echo "--------------------------------\n";
        testTp($t, $c, $param_arr);
        echo "--------------------------------\n";
        echo "--end benchmark: type: {$t}; conn: {$c}--\n";
        echo "--------------------------------\n";
    }
}

echo "done\n";
?>