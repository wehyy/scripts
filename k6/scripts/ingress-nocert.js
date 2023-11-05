import http from 'k6/http';
import { sleep } from 'k6';

export const options = {
    insecureSkipTLSVerify: true,
}

export default function () {
    http.get('https://istio-ingressgateway.istio-system.svc.cluster.local');
    sleep(1);
}