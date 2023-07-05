<?php
//benchmark.php
function testTp($msg_size, $lat_perc) {
    $file = 'result/bench_latency_m'.$msg_size.'_'.$lat_perc.'_10s';
    $strCmd = "kubectl exec netperf-client -c netperf -- netperf -t TCP_RR -H 10.234.1.93 -p 9125 -l 10 -- -r %d, $msg_size, -o $lat_perc";

    $arrAll = array();
    for ($i=1; $i<=40; $i++)
    {
        $req_size = $i*500;
        $cmd = sprintf($strCmd,$req_size);
        echo $file, ": start to exec ",$cmd,"\n";
        $arrExec = array();
        exec($cmd, $arrExec);
        $r = array_pop($arrExec);
        $r1 = preg_split('/\s+/',$r);
        $arrAll[$req_size] = trim($r1[0]);
        $f = sprintf($file,$req_size);
    }
    var_dump($arrAll);
    file_put_contents($f,json_encode($arrAll));
}

$resArr = array(
    1000,
    3000);

$latArr = array(
  'P50_LATENCY',
  'P90_LATENCY',
  'P99_LATENCY');


foreach ($resArr as $v) {
  foreach ($latArr as $w) {
    echo "--------------------------------\n";
    echo "--------开始测试: msg_$v, lat_$w-----------\n";
    echo "--------------------------------\n";
    testTp($v, $w);
  }
}
echo "done\n";
?>