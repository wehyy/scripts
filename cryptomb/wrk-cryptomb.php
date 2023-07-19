<?php
//wrk benchmark, short connection and close reused-session
function testTp($type) {

    $wrkClient = "wrk-out-646c879bdc-ql95n";
    $wrkStr = "wrk -t4 -c60 -d60";
    $topStr = "top -b -n 100 -d 1";
    $httpUrl = "http://172.16.111.88/hello";
    $httpsUrl = "https://172.16.111.88/hello";
    
    if ($type == "httpKeepAlive") {
        $wrkCmd = "kubectl exec -n my-env {$wrkClient} -- {$wrkStr} --latency {$httpUrl}";
    }
    elseif ($type == "httpClose") {
        $wrkCmd = "kubectl exec -n my-env {$wrkClient} -- {$wrkStr} -H 'Connection: Close' --latency {$httpUrl}";
    }
    elseif ($type == :"httpsKeepAlive") {
        $wrkCmd = "kubectl exec -n my-env {$wrkClient} -- {$wrkStr} --latency {$httpsUrl}";
    }
    elseif ($type == "httpsClose") {
        $wrkCmd = "kubectl exec -n my-env {$wrkClient} -- {$wrkStr} -H 'Connection: Close' --latency {$httpsUrl}";
    }
    elseif ($type == "httpsSesionClose") {
        $wrkCmd = "kubectl exec -n my-env {$wrkClient} -- {$wrkStr} -H 'Connection: Close' -m0 --latency {$httpsUrl}";
    }

    $wrkPath = "./cryptomb-result/{$type}/wrk/";
    if(!is_dir($wrkPath)) {
        mkdir($wrkPath, 0777, true);
    }
    foreach (range(1, 5) as $index) {
        $wrkFile = "{$wrkPath}/latency_t4_c60_60s_{$index}";
        echo $wrkFile, ": start to exec ",$wrkCmd,"\n";
        $rawCmd = "{$wrkCmd} > {$wrkFile}";
        exec($rawCmd);
        sleep(20);
    }
}

$cType = array('httpKeepAlive', 'httpClose', 'httpsKeepAlive', 'httpsClose', 'httpsSesionClose');

foreach ($cType as $t) {
    echo "--------------------------------\n";
    echo "--start benchmark: type: {$t}--\n";
    echo "--------------------------------\n";
    testTp($t);
    echo "--------------------------------\n";
    echo "--end benchmark: type: {$t}--\n";
    echo "--------------------------------\n";
}

echo "done\n";
?>