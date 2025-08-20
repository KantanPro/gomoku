/**
 * 五目並べゲームのJavaScript
 */

(function($) {
    'use strict';
    
    class GomokuGame {
        constructor() {
            this.boardSize = 15;
            this.board = [];
            this.currentPlayer = 1; // 1: 黒, 2: 白
            this.gameOver = false;
            this.winner = null;
            this.moveHistory = [];
            this.scores = { black: 0, white: 0 };
            this.aiLevel = 'medium'; // AIレベル
            this.aiPlayer = 2; // 白がAI
            this.isLoggedIn = false; // ログイン状態
            
            this.init();
        }
        
        init() {
            this.initBoard();
            this.bindEvents();
            this.updateUI();
            this.checkAILevel();
            this.checkDarkMode();
            this.checkLoginStatus();
            this.loadGameHistory();
        }
        
        /**
         * ボードの初期化
         */
        initBoard() {
            this.board = [];
            for (let i = 0; i < this.boardSize; i++) {
                this.board[i] = [];
                for (let j = 0; j < this.boardSize; j++) {
                    this.board[i][j] = 0;
                }
            }
        }
        
        /**
         * イベントのバインド
         */
        bindEvents() {
            // セルのクリックイベント
            $(document).on('click', '.gomoku-cell', (e) => {
                if (this.gameOver) return;
                
                const $cell = $(e.target);
                const row = parseInt($cell.data('row'));
                const col = parseInt($cell.data('col'));
                
                this.makeMove(row, col);
            });
            
            // リセットボタン
            $(document).on('click', '#reset-game', () => {
                this.resetGame();
            });
            
            // 一手戻すボタン
            $(document).on('click', '#undo-move', () => {
                this.undoMove();
            });
            
            // AIレベル選択
            $(document).on('change', '#ai-level', (e) => {
                this.changeAILevel($(e.target).val());
            });
        }
        
        /**
         * 石を配置
         */
        makeMove(row, col) {
            if (this.board[row][col] !== 0) return;
            
            this.board[row][col] = this.currentPlayer;
            this.moveHistory.push({ row, col, player: this.currentPlayer });
            
            // UI更新
            this.updateCell(row, col);
            
            // 勝利判定
            if (this.checkWin(row, col)) {
                this.gameOver = true;
                this.winner = this.currentPlayer;
                this.updateScores();
                this.showGameResult();
                this.saveScore();
                return;
            }
            
            // 引き分け判定
            if (this.isBoardFull()) {
                this.gameOver = true;
                this.showGameResult('draw');
                return;
            }
            
            // プレイヤーの交代
            this.currentPlayer = this.currentPlayer === 1 ? 2 : 1;
            this.updateUI();
            
            // AIの手番の場合、自動で手を打つ
            if (this.currentPlayer === this.aiPlayer && !this.gameOver) {
                setTimeout(() => {
                    this.makeAIMove();
                }, 500); // 0.5秒後にAIが手を打つ
            }
        }
        
        /**
         * 勝利判定
         */
        checkWin(row, col) {
            const player = this.board[row][col];
            const directions = [
                [0, 1],   // 水平
                [1, 0],   // 垂直
                [1, 1],   // 右下がり対角線
                [1, -1]   // 左下がり対角線
            ];
            
            for (const [drow, dcol] of directions) {
                let count = 1;
                
                // 正方向のカウント
                count += this.countInDirection(row, col, drow, dcol, player);
                
                // 負方向のカウント
                count += this.countInDirection(row, col, -drow, -dcol, player);
                
                if (count >= 5) {
                    return true;
                }
            }
            
            return false;
        }
        
        /**
         * 特定方向の石の数をカウント
         */
        countInDirection(row, col, drow, dcol, player) {
            let count = 0;
            let r = row + drow;
            let c = col + dcol;
            
            while (r >= 0 && r < this.boardSize && 
                   c >= 0 && c < this.boardSize && 
                   this.board[r][c] === player) {
                count++;
                r += drow;
                c += dcol;
            }
            
            return count;
        }
        
        /**
         * ボードが満杯かチェック
         */
        isBoardFull() {
            for (let i = 0; i < this.boardSize; i++) {
                for (let j = 0; j < this.boardSize; j++) {
                    if (this.board[i][j] === 0) {
                        return false;
                    }
                }
            }
            return true;
        }
        
        /**
         * 一手戻す
         */
        undoMove() {
            if (this.moveHistory.length === 0) return;
            
            const lastMove = this.moveHistory.pop();
            this.board[lastMove.row][lastMove.col] = 0;
            
            // ゲーム状態をリセット
            this.gameOver = false;
            this.winner = null;
            
            // プレイヤーを戻す
            this.currentPlayer = lastMove.player;
            
            // UI更新
            this.updateCell(lastMove.row, lastMove.col);
            this.updateUI();
        }
        
        /**
         * ゲームのリセット
         */
        resetGame() {
            this.initBoard();
            this.currentPlayer = 1;
            this.gameOver = false;
            this.winner = null;
            this.moveHistory = [];
            
            // UI更新
            $('.gomoku-cell').removeClass('black white');
            this.updateUI();
            $('#game-status').text('ゲーム中');
            
            // AIが先手の場合は自動で手を打つ
            if (this.aiPlayer === 1 && !this.gameOver) {
                setTimeout(() => {
                    this.makeAIMove();
                }, 500);
            }
        }
        
        /**
         * セルの更新
         */
        updateCell(row, col) {
            const $cell = $(`.gomoku-cell[data-row="${row}"][data-col="${col}"]`);
            const value = this.board[row][col];
            
            $cell.removeClass('black white');
            if (value === 1) {
                $cell.addClass('black');
            } else if (value === 2) {
                $cell.addClass('white');
            }
        }
        
        /**
         * UIの更新
         */
        updateUI() {
            const playerText = this.currentPlayer === 1 ? '黒' : '白';
            $('#current-player').text(playerText);
            
            if (this.gameOver) {
                $('#game-status').text('ゲーム終了');
            } else {
                $('#game-status').text('ゲーム中');
            }
        }
        
        /**
         * ゲーム結果の表示
         */
        showGameResult(result = null) {
            let message = '';
            if (result === 'draw') {
                message = '引き分けです！';
            } else if (this.winner) {
                const winnerText = this.winner === 1 ? '黒' : '白';
                message = `${winnerText}の勝利です！`;
            }
            
            if (message) {
                setTimeout(() => {
                    alert(message);
                }, 100);
            }
        }
        
        /**
         * スコアの更新
         */
        updateScores() {
            if (this.winner === 1) {
                this.scores.black++;
                $('#score-black').text(this.scores.black);
            } else if (this.winner === 2) {
                this.scores.white++;
                $('#score-white').text(this.scores.white);
            }
        }
        
        /**
         * スコアの保存
         */
        saveScore() {
            if (!this.winner) return;
            
            const winnerText = this.winner === 1 ? '黒' : '白';
            
            // ログインユーザーのみスコアを保存
            if (this.isLoggedIn) {
                $.ajax({
                    url: gomoku_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'gomoku_save_score',
                        winner: winnerText,
                        moves: this.moveHistory.length,
                        nonce: gomoku_ajax.nonce
                    },
                    success: (response) => {
                        console.log('スコアが保存されました');
                        this.addGameHistory(winnerText, this.moveHistory.length);
                    },
                    error: function() {
                        console.log('スコアの保存に失敗しました');
                    }
                });
            } else {
                console.log('ゲストユーザーのため、スコアは保存されません');
            }
        }
        
        /**
         * ゲーム履歴に追加
         */
        addGameHistory(winner, moves) {
            const historyList = $('#game-history');
            const now = new Date();
            const timeString = now.toLocaleTimeString('ja-JP', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            
            const historyItem = `
                <div class="history-item">
                    <span class="history-time">${timeString}</span>
                    <span class="history-winner">${winner}の勝利</span>
                    <span class="history-moves">${moves}手</span>
                </div>
            `;
            
            historyList.prepend(historyItem);
            
            // 履歴が多すぎる場合は古いものを削除
            const historyItems = historyList.find('.history-item');
            if (historyItems.length > 10) {
                historyItems.slice(10).remove();
            }
            
            // ローカルストレージに保存
            this.saveHistoryToStorage();
        }
        
        /**
         * ゲーム履歴を読み込み
         */
        loadGameHistory() {
            // ログインユーザーのみ履歴を読み込み
            if (!this.isLoggedIn) {
                console.log('ゲストユーザーのため、履歴は読み込まれません');
                return;
            }
            
            // ローカルストレージから履歴を読み込み
            const savedHistory = localStorage.getItem('gomoku_history');
            if (savedHistory) {
                try {
                    const history = JSON.parse(savedHistory);
                    this.displayHistory(history);
                } catch (e) {
                    console.log('履歴の読み込みに失敗しました');
                }
            }
        }
        
        /**
         * 履歴を表示
         */
        displayHistory(history) {
            const historyList = $('#game-history');
            historyList.empty();
            
            history.forEach(item => {
                const historyItem = `
                    <div class="history-item">
                        <span class="history-time">${item.time}</span>
                        <span class="history-winner">${item.winner}の勝利</span>
                        <span class="history-moves">${item.moves}手</span>
                    </div>
                `;
                historyList.append(historyItem);
            });
        }
        
        /**
         * 履歴をローカルストレージに保存
         */
        saveHistoryToStorage() {
            const historyList = $('#game-history');
            const historyItems = historyList.find('.history-item');
            const history = [];
            
            historyItems.each(function() {
                const time = $(this).find('.history-time').text();
                const winner = $(this).find('.history-winner').text().replace('の勝利', '');
                const moves = $(this).find('.history-moves').text().replace('手', '');
                
                history.push({
                    time: time,
                    winner: winner,
                    moves: moves
                });
            });
            
            localStorage.setItem('gomoku_history', JSON.stringify(history));
        }
        
        /**
         * AIレベルの確認
         */
        checkAILevel() {
            const aiLevelAttr = $('#gomoku-game').data('ai-level');
            if (aiLevelAttr) {
                this.aiLevel = aiLevelAttr;
                $('#ai-level').val(this.aiLevel);
            }
        }
        
        /**
         * AIレベルの変更
         */
        changeAILevel(level) {
            this.aiLevel = level;
            console.log('AIレベルを変更しました:', level);
        }
        
        /**
         * AIの手を打つ
         */
        makeAIMove() {
            if (this.currentPlayer !== this.aiPlayer || this.gameOver) {
                return;
            }
            
            // AIの思考時間を演出
            $('#game-status').text('AIが考え中...');
            
            setTimeout(() => {
                const move = this.findBestMove();
                if (move) {
                    this.makeMove(move.row, move.col);
                }
            }, 1000 + Math.random() * 1000); // 1-2秒のランダムな思考時間
        }
        
        /**
         * AIの最適な手を探す
         */
        findBestMove() {
            let bestScore = -Infinity;
            let bestMove = null;
            let candidates = [];
            
            // 空いているマスを全て評価
            for (let i = 0; i < this.boardSize; i++) {
                for (let j = 0; j < this.boardSize; j++) {
                    if (this.board[i][j] === 0) {
                        const score = this.evaluatePosition(i, j);
                        candidates.push({ row: i, col: j, score: score });
                        
                        if (score > bestScore) {
                            bestScore = score;
                            bestMove = { row: i, col: j };
                        }
                    }
                }
            }
            
            // AIレベルに応じた戦略
            if (this.aiLevel === 'easy') {
                return this.getRandomMove(candidates);
            } else if (this.aiLevel === 'medium') {
                return this.getMediumMove(candidates, bestScore);
            } else {
                return bestMove; // 上級は最適な手を選択
            }
        }
        
        /**
         * 初級AI: ランダムな手を選択
         */
        getRandomMove(candidates) {
            if (candidates.length === 0) return null;
            const randomIndex = Math.floor(Math.random() * candidates.length);
            return candidates[randomIndex];
        }
        
        /**
         * 中級AI: 時々ミスをする
         */
        getMediumMove(candidates, bestScore) {
            if (candidates.length === 0) return null;
            
            // 30%の確率でランダムな手を選択（ミス）
            if (Math.random() < 0.3) {
                return this.getRandomMove(candidates);
            }
            
            // 70%の確率で良い手を選択
            const goodMoves = candidates.filter(move => move.score >= bestScore * 0.7);
            if (goodMoves.length > 0) {
                const randomIndex = Math.floor(Math.random() * goodMoves.length);
                return goodMoves[randomIndex];
            }
            
            return candidates[0];
        }
        
        /**
         * 特定の位置のスコアを評価
         */
        evaluatePosition(row, col) {
            let score = 0;
            
            // AIプレイヤー（白）の視点で評価
            const aiPlayer = this.aiPlayer;
            const humanPlayer = this.aiPlayer === 1 ? 2 : 1;
            
            // この位置にAIが置いた場合の評価
            this.board[row][col] = aiPlayer;
            score += this.evaluateBoard(aiPlayer) * 10; // AIの勝利可能性
            
            // この位置に人間が置いた場合の評価（ブロック）
            this.board[row][col] = humanPlayer;
            score += this.evaluateBoard(humanPlayer) * 8; // 人間の勝利阻止
            
            // 位置を元に戻す
            this.board[row][col] = 0;
            
            // 中央に近い位置を優先
            const center = (this.boardSize - 1) / 2;
            const distanceToCenter = Math.abs(row - center) + Math.abs(col - center);
            score += (10 - distanceToCenter) * 2;
            
            return score;
        }
        
        /**
         * ボード全体の評価
         */
        evaluateBoard(player) {
            let score = 0;
            
            // 全ての方向で連続する石の数をチェック
            for (let i = 0; i < this.boardSize; i++) {
                for (let j = 0; j < this.boardSize; j++) {
                    if (this.board[i][j] === player) {
                        score += this.evaluateCell(i, j, player);
                    }
                }
            }
            
            return score;
        }
        
        /**
         * 特定のセルの評価
         */
        evaluateCell(row, col, player) {
            const directions = [
                [0, 1],   // 水平
                [1, 0],   // 垂直
                [1, 1],   // 右下がり対角線
                [1, -1]   // 左下がり対角線
            ];
            
            let totalScore = 0;
            
            for (const [drow, dcol] of directions) {
                let count = 1;
                let blocked = 0;
                
                // 正方向のカウント
                count += this.countInDirection(row, col, drow, dcol, player);
                if (this.isBlocked(row, col, drow, dcol, player)) {
                    blocked++;
                }
                
                // 負方向のカウント
                count += this.countInDirection(row, col, -drow, -dcol, player);
                if (this.isBlocked(row, col, -drow, -dcol, player)) {
                    blocked++;
                }
                
                totalScore += this.calculateLineScore(count, blocked);
            }
            
            return totalScore;
        }
        
        /**
         * 特定方向がブロックされているかチェック
         */
        isBlocked(row, col, drow, dcol, player) {
            const r = row + drow;
            const c = col + dcol;
            
            if (r < 0 || r >= this.boardSize || c < 0 || c >= this.boardSize) {
                return true; // 境界でブロック
            }
            
            return (this.board[r][c] !== 0 && this.board[r][c] !== player);
        }
        
        /**
         * 連続する石の数に基づくスコア計算
         */
        calculateLineScore(count, blocked) {
            if (count >= 5) return 10000; // 勝利
            
            const scores = {
                4: 1000,   // 4つ並び
                3: 100,    // 3つ並び
                2: 10,     // 2つ並び
                1: 1       // 1つ
            };
            
            let baseScore = scores[count] || 0;
            
            // ブロックされていない場合はボーナス
            if (blocked === 0) {
                baseScore *= 2;
            }
            
            return baseScore;
        }
        
        /**
         * ダークモードの検出と適用
         */
        checkDarkMode() {
            const darkThemeAttr = $('#gomoku-game').data('dark-theme');
            console.log('テーマ設定:', darkThemeAttr);
            
            // 明示的にダークテーマが指定されている場合
            if (darkThemeAttr === 'true' || darkThemeAttr === 'dark') {
                this.applyDarkMode();
                return;
            }
            
            // 明示的にライトテーマが指定されている場合
            if (darkThemeAttr === 'false' || darkThemeAttr === 'light') {
                this.removeDarkMode();
                return;
            }
            
            // 自動検出の場合（auto）
            this.detectAndApplyDarkMode();
        }
        
        /**
         * ダークモードの自動検出と適用
         */
        detectAndApplyDarkMode() {
            // システムのダークモード設定をチェック
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                this.applyDarkMode();
            } else {
                this.removeDarkMode();
            }
            
            // ダークモードの変更を監視
            if (window.matchMedia) {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                    if (e.matches) {
                        this.applyDarkMode();
                    } else {
                        this.removeDarkMode();
                    }
                });
            }
            
            // ページ内のダークテーマクラスをチェック
            this.checkPageDarkMode();
        }
        
        /**
         * ページ内のダークテーマクラスをチェック
         */
        checkPageDarkMode() {
            const hasDarkClass = document.body.classList.contains('dark-theme') || 
                                document.body.classList.contains('dark-mode') ||
                                document.body.classList.contains('dark') ||
                                document.documentElement.classList.contains('dark-theme') ||
                                document.documentElement.classList.contains('dark-mode') ||
                                document.documentElement.classList.contains('dark') ||
                                document.body.classList.contains('wp-dark-mode') ||
                                document.body.classList.contains('dark-theme-active');
            
            if (hasDarkClass) {
                this.applyDarkMode();
            } else {
                this.removeDarkMode();
            }
            
            // ページのクラス変更を監視
            this.observePageChanges();
        }
        
        /**
         * ページの変更を監視
         */
        observePageChanges() {
            // MutationObserverを使用してページのクラス変更を監視
            if (window.MutationObserver) {
                const observer = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                            this.checkPageDarkMode();
                        }
                    });
                });
                
                observer.observe(document.body, {
                    attributes: true,
                    attributeFilter: ['class']
                });
                
                observer.observe(document.documentElement, {
                    attributes: true,
                    attributeFilter: ['class']
                });
            }
        }
        
        /**
         * ダークモードを適用
         */
        applyDarkMode() {
            $('#gomoku-game').addClass('dark-theme');
            console.log('ダークテーマを適用しました');
        }
        
        /**
         * ダークモードを削除
         */
        removeDarkMode() {
            $('#gomoku-game').removeClass('dark-theme');
            console.log('ライトテーマを適用しました');
        }
        
        /**
         * ログイン状態をチェック
         */
        checkLoginStatus() {
            // ゲーム履歴セクションの存在でログイン状態を判定
            if ($('#game-history').length > 0) {
                this.isLoggedIn = true;
                console.log('ログインユーザーとして認識しました');
            } else {
                this.isLoggedIn = false;
                console.log('ゲストユーザーとして認識しました');
            }
        }
    }
    
    // DOM読み込み完了後にゲームを初期化
    $(document).ready(function() {
        new GomokuGame();
    });
    
})(jQuery);
