<?php
//wrk benchmark, short connection and close reused-session
function testTp($type, $params, $ingressProxy) {

    $mode = $param_arr['m'];
    $cpus = $param_arr['c'];
    $wrkStr = "wrk -t8 -c128 -d40";
    $topStr = "top -b -n 60 -d 0.5";
    // $ingressProxy = "istio-ingressgateway-856645d666-lxmcp";
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

    $topCmd = "kubectl exec -n istio-system {$ingressProxy} -it -- $topStr | grep envoy";

    $wrkPath = "./../wrk-limit-result/{$mode}-cpu{$cpus}/{$type}/wrk";
    if(!is_dir($wrkPath)) {
        mkdir($wrkPath, 0777, true);
    }
    $topPath = "./../wrk-limit-result/{$mode}-cpu{$cpus}/{$type}/top";
    if(!is_dir($topPath)) {
        mkdir($topPath, 0777, true);
    }

    foreach (range(1, 5) as $index) {
        $wrkFile = "{$wrkPath}/latency_t8_c128_40s_{$index}";
        $topFile = "{$topPath}/proxy_cpu_t8_c128_40s_{$index}";
        foreach (range(0, 1) as $thread_id) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                echo "failed to fork\n";
                exit;
            } elseif ($pid > 0) {
                $pid = posix_getpid();
                echo "there is the parent thread, pid: $pid\n";
            } else {
                if ($thread_id == 0) {
                    echo $wrkFile, ": start to exec ",$wrkCmd,"\n";
                    $rawCmd = "{$wrkCmd} > {$wrkFile}";
                    exec($rawCmd);
                    exit;
                } else {
                    $raw1Cmd = "{$topCmd} > {$topFile}";
                    echo ": start to exec ", $raw1Cmd, "\n";
                    exec($raw1Cmd);
                    exit;
                }
            }
        }
        sleep(50);
    }
}

$cType = array('httpsSesionClose');

//m:mode, c:cpus
$param_arr = getopt('m:c:');
if (empty($param_arr)) {
    echo "usage: php wrk-maxqps.php -m cryptomb -c 6\n";
    exit;
}
print_r($param_arr);

$execArr = array();
$getIngressCmd = "kubectl get pod -n istio-system | grep ingressgateway | awk '{print $1}'";
exec($getIngressCmd, $execArr);
$ingressProxy = $execArr[0];

foreach ($cType as $t) {
    echo "--------------------------------\n";
    echo "--start benchmark: type: {$t}--\n";
    echo "--------------------------------\n";
    testTp($t, $param_arr, $ingressProxy);
    echo "--------------------------------\n";
    echo "--end benchmark: type: {$t}--\n";
    echo "--------------------------------\n";
}

echo "done\n";
?>