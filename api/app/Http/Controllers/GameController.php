<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GameMatch;
use App\Models\Game;
use Carbon\Carbon;
use App\Models\User;
use App\Models\CoinTransaction;
use Illuminate\Support\Facades\DB;
use App\Models\Notification;


class GameController extends Controller
{
    public function create($match_id)
    {
        $match = GameMatch::findOrFail($match_id);

        if($match->status !== 'Playing') {
            return response()->json(['message' => 'Esta partida não está ativa.'], 400);
        }

        $game = Game::create([
            'match_id'        => $match->id,
            'player1_user_id' => $match->player1_user_id,
            'player2_user_id' => $match->player1_user_id,
            'type'            => $match->type,
            'status'          => 'Playing',
            'began_at'        => now(),
            'player1_points'  => 0,
            'player2_points'  => 0
        ]);

        return response()->json([
            'status' => 201,
            'message' => 'Jogo criado com sucesso',
            'data' => $game,
            'typeOfBisca' => $match->type
        ]);
    }

public function finishGame(Request $request, $game_id)
    {
        $request->validate([
            'player1_points' => 'required|integer|min:0|max:120'
        ]);

        return DB::transaction(function () use ($request, $game_id) {
            $game = Game::findOrFail($game_id);

            if ($game->status !== 'Playing') {
                return response()->json(['message' => 'Este jogo já foi finalizado'], 400);
            }

            $match = GameMatch::lockForUpdate()->findOrFail($game->match_id);

            // --- 1. CAPTURAR OS RECORDES GLOBAIS ATUAIS (ANTES DE ATUALIZAR) ---
            $maxWins      = $this->getCurrentMaxWins();
            $maxCoins     = $this->getCurrentMaxCoins();
            $maxCapotes   = $this->getCurrentMaxCapotes();
            $maxBandeiras = $this->getCurrentMaxBandeiras();

            // Lógica de pontos
            $p1Points = (int) $request->player1_points;
            $p2Points = 120 - $p1Points;

            $game->ended_at = now();
            $game->total_time = (int) Carbon::parse($game->began_at)->diffInSeconds(now());
            $game->player1_points = $p1Points;
            $game->player2_points = $p2Points;
            $game->status = 'Ended';
            $game->custom = $request->trickByTrick;

            if ($p1Points > $p2Points) {
                $game->winner_user_id = $game->player1_user_id;
            } elseif ($p2Points > $p1Points) {
                $game->loser_user_id = $game->player1_user_id;
            } else {
                $game->is_draw = 1;
            }
            $game->save();

            // Atualizar Marcas
            if ($p1Points == 120) { $match->player1_marks += 4; }
            elseif ($p1Points > 90) { $match->player1_marks += 2; }
            elseif ($p1Points > 60) { $match->player1_marks += 1; }
            elseif ($p1Points >= 30) { $match->player2_marks += 1; }
            elseif ($p1Points > 0) { $match->player2_marks += 2; }
            else { $match->player2_marks += 4; }

            // Buscar todos os jogos terminados desta partida
            $games = Game::where('match_id', $match->id)->where('status', 'Ended')->get();
            $coinsEarned = 0;

            // Verificar se a PARTIDA (Match) terminou (4 marcas ou mais)
            if ($match->player1_marks >= 4 || $match->player2_marks >= 4) {

                $match->status = 'Ended';
                $match->ended_at = now();
                $match->total_time = $games->sum('total_time');
                $match->player1_points = $games->sum('player1_points');
                $match->player2_points = $games->sum('player2_points');

                $match->save();

                // Se o jogador ganhou a partida
                if($match->player1_marks >= 4){
                    $match->winner_user_id = $match->player1_user_id;
                    $match->loser_user_id = null;

                    // Calcular moedas
                    $coinsEarned = 5;
                    foreach($games as $g){
                        if($g->player1_points == 120) {
                            $coinsEarned = 20;
                        }
                        elseif($g->player1_points > 90 && $g->player1_points < 120) {
                            $coinsEarned += 3;
                        }
                    }

                    // Atualizar User
                    $user = User::find($match->player1_user_id);
                    $user->coins_balance += $coinsEarned;
                    $user->save();

                    // Criar Transação
                    CoinTransaction::create([
                        'transaction_datetime' => now(),
                        'match_id' => $match->id,
                        'user_id' => $user->id,
                        'coin_transaction_type_id' => 6,
                        'coins' => $coinsEarned,
                    ]);

                    // --- 2. VERIFICAR NOTIFICAÇÕES (RECORDES) ---

                    // A. Most Matches Won
                    $myTotalWins = GameMatch::where('status', 'Ended')->where('winner_user_id', $user->id)->count();
                    if ($myTotalWins > $maxWins) {
                        $this->notifyAllUsers('LEADERBOARD', 'New Wins Leader!', "Player {$user->nickname} is now the champion with {$myTotalWins} wins!");
                    }

                    // B. Most Coins Earned (Total acumulado type=6)
                    $myTotalCoins = CoinTransaction::where('user_id', $user->id)->where('coin_transaction_type_id', 6)->sum('coins');
                    if ($myTotalCoins > $maxCoins) {
                        $this->notifyAllUsers('LEADERBOARD', 'New Tycoon!', "Player {$user->nickname} earned a record breaking {$myTotalCoins} coins!");
                    }

                    // C. Most Capotes
                    $myTotalCapotes = Game::where('status', 'Ended')->where('player1_user_id', $user->id)->whereBetween('player1_points', [91, 119])->count();
                    if ($myTotalCapotes > $maxCapotes) {
                        $this->notifyAllUsers('LEADERBOARD', 'Capote Master!', "Player {$user->nickname} is the new Capote master with {$myTotalCapotes} capotes!");
                    }

                    // D. Most Bandeiras
                    $myTotalBandeiras = Game::where('status', 'Ended')->where('player1_user_id', $user->id)->where('player1_points', 120)->count();
                    if ($myTotalBandeiras > $maxBandeiras) {
                        $this->notifyAllUsers('LEADERBOARD', 'Bandeira Legend!', "Player {$user->nickname} has the most Bandeiras: {$myTotalBandeiras}!");
                    }

                } else {
                    // Jogador perdeu
                    $match->winner_user_id = null;
                    $match->loser_user_id = $match->player1_user_id;
                }

                $match->save();

                // RESPOSTA COMPLETA (MATCH ENDED)
                return response()->json([
                    'status' => 201,
                    'message' => 'Jogo e partida finalizados com sucesso',
                    'match' => $match,
                    'games' => $games,
                    'coinsEarned' => $coinsEarned ? $coinsEarned : 0
                ]);
            }

            $match->save();

            // RESPOSTA COMPLETA (GAME ENDED, MATCH CONTINUES)
            return response()->json([
                'status' => 200,
                'message' => 'Jogo finalizado com sucesso',
                'data' => $game,
                'gameNumber' => $games->count(),
                'match' => $match
            ]);
        });
    }

    public function finishedGamesByUser($user_id){
        $matches = Game::where('player1_user_id', $user_id)->where('status', 'Ended')->orderBy('began_at', 'desc')->get();

        return response()->json([
            'status' => 200,
            'data' => $matches
        ]);
    }

    // --- MÉTODOS AUXILIARES PARA CALCULAR TOTAIS GLOBAIS ---

    private function getCurrentMaxWins() {
        return GameMatch::where('status', 'Ended')
            ->whereNotNull('winner_user_id')
            ->selectRaw('count(*) as total')
            ->groupBy('winner_user_id')
            ->orderByDesc('total')
            ->value('total') ?? 0;
    }

    private function getCurrentMaxCoins() {
        return CoinTransaction::where('coin_transaction_type_id', 6)
            ->selectRaw('sum(coins) as total')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->value('total') ?? 0;
    }

    private function getCurrentMaxCapotes() {
        return Game::where('status', 'Ended')
            ->whereBetween('player1_points', [91, 119])
            ->selectRaw('count(*) as total')
            ->groupBy('player1_user_id')
            ->orderByDesc('total')
            ->value('total') ?? 0;
    }

    private function getCurrentMaxBandeiras() {
        return Game::where('status', 'Ended')
            ->where('player1_points', 120)
            ->selectRaw('count(*) as total')
            ->groupBy('player1_user_id')
            ->orderByDesc('total')
            ->value('total') ?? 0;
    }

    private function notifyAllUsers($type, $title, $message) {
        $users = User::all();
        foreach ($users as $u) {
            Notification::create([
                'user_id' => $u->id,
                'type' => $type,
                'title' => $title,
                'message' => $message
            ]);
        }
    }
}
