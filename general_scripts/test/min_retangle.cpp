#include<iostream>
#include<vector>
#include<algorithm>
using namespace std;

int minPlannedAreas(int m, int n) {
    vector<vector<int> > dp(m+1, vector<int>(n+1, 0));
    for (int i = 1; i <= m; i++) dp[i][1] = i;
    for (int j = 1; j <= n; j++) dp[1][j] = j;
    for (int i = 2; i <= m; i++) {
        for (int j = 2; j <= n; j++) {
            if (i == j) dp[i][j] = 1;
            else {
                int minarea = 1000000;
                for (int k = 1; k < i; k++) minarea = min(minarea, dp[i-k][j] + dp[k][j]);
                for (int k = 1; k < j; k++) minarea = min(minarea, dp[i][j-k] + dp[i][k]);
                dp[i][j] = minarea;
            }
        }
    }
    return dp[m][n];
}

int main() {
    int n, m;
    cin >> n >> m;
    cout << minPlannedAreas(m, n) << endl;
}