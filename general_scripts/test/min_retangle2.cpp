#include<iostream>
#include<vector>
using namespace std;

bool matrix[15][15];
int m, n;
int ans = 10000;

bool check(int x, int y, int len) {
    for (int i = 0; i < len; i++) {
        for (int j = 0; j < len; j++) {
            if(matrix[x+i][y+j]) return false;
        }
    }
    return true;
}

void fill(int x, int y, int len) {
    for (int i = 0; i < len; i++) {
        for (int j =0; j < len; j++) {
            matrix[x+i][y+j] ^= 1;
        }
    }
}

void dfs(int x, int y, int cnt) {
    if (cnt >= ans) return;
    if (x == m + 1) {
        ans = cnt;
        return;
    }
    if (y > n + 1) dfs(x+1, 1, cnt);
    bool full = true;
    for (int i = y; i <= n; i++) {
        if (!matrix[x][i]) {
            full = false;
            for (int j = min(n-i+1, m-x+1); j >= 1; j--) {
                if(check(x, i, j)) {
                    fill(x, i, j);
                    dfs(x, y+j, cnt+1);
                    fill(x, i, j);
                }
            }
            break;
        }
    }
    if (full) dfs(x+1, 1, cnt);
}

int main() {
    cin >> m >> n;
    dfs(1, 1, 0);
    cout << ans << endl;
    return 0;
}