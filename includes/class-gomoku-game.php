<?php
/**
 * 五目並べゲームのロジッククラス
 */

if (!defined('ABSPATH')) {
    exit;
}

class Gomoku_Game {
    
    private $board_size;
    private $board;
    private $current_player;
    private $game_over;
    private $winner;
    private $move_history;
    
    public function __construct($board_size = 15) {
        $this->board_size = $board_size;
        $this->reset_game();
    }
    
    /**
     * ゲームのリセット
     */
    public function reset_game() {
        $this->board = array();
        for ($i = 0; $i < $this->board_size; $i++) {
            $this->board[$i] = array();
            for ($j = 0; $j < $this->board_size; $j++) {
                $this->board[$i][$j] = 0; // 0: 空, 1: 黒, 2: 白
            }
        }
        $this->current_player = 1; // 黒から開始
        $this->game_over = false;
        $this->winner = null;
        $this->move_history = array();
    }
    
    /**
     * 石を配置
     */
    public function make_move($row, $col) {
        if ($this->game_over || $this->board[$row][$col] !== 0) {
            return false;
        }
        
        $this->board[$row][$col] = $this->current_player;
        $this->move_history[] = array(
            'row' => $row,
            'col' => $col,
            'player' => $this->current_player
        );
        
        // 勝利判定
        if ($this->check_win($row, $col)) {
            $this->game_over = true;
            $this->winner = $this->current_player;
            return 'win';
        }
        
        // 引き分け判定
        if ($this->is_board_full()) {
            $this->game_over = true;
            return 'draw';
        }
        
        // プレイヤーの交代
        $this->current_player = ($this->current_player === 1) ? 2 : 1;
        return 'continue';
    }
    
    /**
     * 勝利判定
     */
    private function check_win($row, $col) {
        $player = $this->board[$row][$col];
        $directions = array(
            array(0, 1),   // 水平
            array(1, 0),   // 垂直
            array(1, 1),   // 右下がり対角線
            array(1, -1)   // 左下がり対角線
        );
        
        foreach ($directions as $dir) {
            $count = 1; // 現在の位置を含む
            
            // 正方向のカウント
            $count += $this->count_in_direction($row, $col, $dir[0], $dir[1], $player);
            
            // 負方向のカウント
            $count += $this->count_in_direction($row, $col, -$dir[0], -$dir[1], $player);
            
            if ($count >= 5) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 特定方向の石の数をカウント
     */
    private function count_in_direction($row, $col, $drow, $dcol, $player) {
        $count = 0;
        $r = $row + $drow;
        $c = $col + $dcol;
        
        while ($r >= 0 && $r < $this->board_size && 
               $c >= 0 && $c < $this->board_size && 
               $this->board[$r][$c] === $player) {
            $count++;
            $r += $drow;
            $c += $dcol;
        }
        
        return $count;
    }
    
    /**
     * ボードが満杯かチェック
     */
    private function is_board_full() {
        for ($i = 0; $i < $this->board_size; $i++) {
            for ($j = 0; $j < $this->board_size; $j++) {
                if ($this->board[$i][$j] === 0) {
                    return false;
                }
            }
        }
        return true;
    }
    
    /**
     * 一手戻す
     */
    public function undo_move() {
        if (empty($this->move_history)) {
            return false;
        }
        
        $last_move = array_pop($this->move_history);
        $this->board[$last_move['row']][$last_move['col']] = 0;
        
        // ゲーム状態をリセット
        $this->game_over = false;
        $this->winner = null;
        
        // プレイヤーを戻す
        $this->current_player = $last_move['player'];
        
        return true;
    }
    
    /**
     * ゲーム状態の取得
     */
    public function get_game_state() {
        return array(
            'board' => $this->board,
            'current_player' => $this->current_player,
            'game_over' => $this->game_over,
            'winner' => $this->winner,
            'move_count' => count($this->move_history)
        );
    }
    
    /**
     * ボードサイズの取得
     */
    public function get_board_size() {
        return $this->board_size;
    }
    
    /**
     * 現在のプレイヤーの取得
     */
    public function get_current_player() {
        return $this->current_player;
    }
    
    /**
     * ゲーム終了状態の取得
     */
    public function is_game_over() {
        return $this->game_over;
    }
    
    /**
     * 勝利者の取得
     */
    public function get_winner() {
        return $this->winner;
    }
    
    /**
     * AI対戦モードの設定
     */
    public function set_ai_mode($enabled, $ai_player = 2) {
        $this->ai_mode = $enabled;
        $this->ai_player = $ai_player;
    }
    
    /**
     * AIの次の手を計算
     */
    public function get_ai_move() {
        if (!$this->ai_mode || $this->current_player !== $this->ai_player) {
            return false;
        }
        
        // 攻撃優先のAI戦略
        $move = $this->find_best_move();
        return $move;
    }
    
    /**
     * 最適な手を探す
     */
    private function find_best_move() {
        $best_score = -1000;
        $best_move = null;
        
        // 空いているマスを全て評価
        for ($i = 0; $i < $this->board_size; $i++) {
            for ($j = 0; $j < $this->board_size; $j++) {
                if ($this->board[$i][$j] === 0) {
                    // この位置に置いた場合のスコアを計算
                    $score = $this->evaluate_position($i, $j);
                    
                    if ($score > $best_score) {
                        $best_score = $score;
                        $best_move = array('row' => $i, 'col' => $j);
                    }
                }
            }
        }
        
        return $best_move;
    }
    
    /**
     * 特定の位置のスコアを評価
     */
    private function evaluate_position($row, $col) {
        $score = 0;
        
        // AIプレイヤー（白）の視点で評価
        $ai_player = $this->ai_player;
        $human_player = ($ai_player === 1) ? 2 : 1;
        
        // この位置にAIが置いた場合の評価
        $this->board[$row][$col] = $ai_player;
        $score += $this->evaluate_board($ai_player) * 10; // AIの勝利可能性
        
        // この位置に人間が置いた場合の評価（ブロック）
        $this->board[$row][$col] = $human_player;
        $score += $this->evaluate_board($human_player) * 8; // 人間の勝利阻止
        
        // 位置を元に戻す
        $this->board[$row][$col] = 0;
        
        // 中央に近い位置を優先
        $center = ($this->board_size - 1) / 2;
        $distance_to_center = abs($row - $center) + abs($col - $center);
        $score += (10 - $distance_to_center) * 2;
        
        return $score;
    }
    
    /**
     * ボード全体の評価
     */
    private function evaluate_board($player) {
        $score = 0;
        
        // 全ての方向で連続する石の数をチェック
        for ($i = 0; $i < $this->board_size; $i++) {
            for ($j = 0; $j < $this->board_size; $j++) {
                if ($this->board[$i][$j] === $player) {
                    $score += $this->evaluate_cell($i, $j, $player);
                }
            }
        }
        
        return $score;
    }
    
    /**
     * 特定のセルの評価
     */
    private function evaluate_cell($row, $col, $player) {
        $directions = array(
            array(0, 1),   // 水平
            array(1, 0),   // 垂直
            array(1, 1),   // 右下がり対角線
            array(1, -1)   // 左下がり対角線
        );
        
        $total_score = 0;
        
        foreach ($directions as $dir) {
            $count = 1;
            $blocked = 0;
            
            // 正方向のカウント
            $count += $this->count_in_direction($row, $col, $dir[0], $dir[1], $player);
            if ($this->is_blocked($row, $col, $dir[0], $dir[1], $player)) {
                $blocked++;
            }
            
            // 負方向のカウント
            $count += $this->count_in_direction($row, $col, -$dir[0], -$dir[1], $player);
            if ($this->is_blocked($row, $col, -$dir[0], -$dir[1], $player)) {
                $blocked++;
            }
            
            $total_score += $this->calculate_line_score($count, $blocked);
        }
        
        return $total_score;
    }
    
    /**
     * 特定方向がブロックされているかチェック
     */
    private function is_blocked($row, $col, $drow, $dcol, $player) {
        $r = $row + $drow;
        $c = $col + $dcol;
        
        if ($r < 0 || $r >= $this->board_size || $c < 0 || $c >= $this->board_size) {
            return true; // 境界でブロック
        }
        
        return ($this->board[$r][$c] !== 0 && $this->board[$r][$c] !== $player);
    }
    
    /**
     * 連続する石の数に基づくスコア計算
     */
    private function calculate_line_score($count, $blocked) {
        if ($count >= 5) return 10000; // 勝利
        
        $scores = array(
            4 => 1000,   // 4つ並び
            3 => 100,    // 3つ並び
            2 => 10,     // 2つ並び
            1 => 1       // 1つ
        );
        
        $base_score = isset($scores[$count]) ? $scores[$count] : 0;
        
        // ブロックされていない場合はボーナス
        if ($blocked === 0) {
            $base_score *= 2;
        }
        
        return $base_score;
    }
    
    // AI関連のプロパティ
    private $ai_mode = false;
    private $ai_player = 2; // デフォルトで白がAI
}
