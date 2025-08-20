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
            this.aiMode = false;
            this.aiPlayer = 2; // 白がAI
            
            this.init();
        }
        
        init() {
            this.initBoard();
            this.bindEvents();
            this.updateUI();
            this.checkAIMode();
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
            
            // AI対戦トグルボタン
            $(document).on('click', '#toggle-ai', () => {
                this.toggleAIMode();
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
            if (this.aiMode && this.currentPlayer === this.aiPlayer && !this.gameOver) {
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
            
            // AIモードの場合、AIが先手の場合は自動で手を打つ
            if (this.aiMode && this.aiPlayer === 1 && !this.gameOver) {
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
            
            $.ajax({
                url: gomoku_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gomoku_save_score',
                    winner: winnerText,
                    moves: this.moveHistory.length,
                    nonce: gomoku_ajax.nonce
                },
                success: function(response) {
                    console.log('スコアが保存されました');
                },
                error: function() {
                    console.log('スコアの保存に失敗しました');
                }
            });
        }
        
        /**
         * AIモードの確認
         */
        checkAIMode() {
            const aiModeAttr = $('#gomoku-game').data('ai-mode');
            if (aiModeAttr === 'true') {
                this.aiMode = true;
                this.updateAIToggleButton();
            }
        }
        
        /**
         * AIモードの切り替え
         */
        toggleAIMode() {
            this.aiMode = !this.aiMode;
            this.updateAIToggleButton();
            
            if (this.aiMode) {
                // AIモード開始時の処理
                if (this.currentPlayer === this.aiPlayer && !this.gameOver) {
                    setTimeout(() => {
                        this.makeAIMove();
                    }, 500);
                }
            }
        }
        
        /**
         * AIトグルボタンの更新
         */
        updateAIToggleButton() {
            const button = $('#toggle-ai');
            if (this.aiMode) {
                button.text('AI対戦: ON').removeClass('gomoku-btn-ai-off').addClass('gomoku-btn-ai-on');
            } else {
                button.text('AI対戦: OFF').removeClass('gomoku-btn-ai-on').addClass('gomoku-btn-ai-off');
            }
        }
        
        /**
         * AIの手を打つ
         */
        makeAIMove() {
            if (!this.aiMode || this.currentPlayer !== this.aiPlayer || this.gameOver) {
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
            
            // 空いているマスを全て評価
            for (let i = 0; i < this.boardSize; i++) {
                for (let j = 0; j < this.boardSize; j++) {
                    if (this.board[i][j] === 0) {
                        const score = this.evaluatePosition(i, j);
                        if (score > bestScore) {
                            bestScore = score;
                            bestMove = { row: i, col: j };
                        }
                    }
                }
            }
            
            return bestMove;
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
    }
    
    // DOM読み込み完了後にゲームを初期化
    $(document).ready(function() {
        new GomokuGame();
    });
    
})(jQuery);
