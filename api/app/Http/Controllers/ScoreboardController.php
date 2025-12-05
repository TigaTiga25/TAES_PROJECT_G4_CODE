<?php
namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use App\Models\GameMatch;
use App\Models\Game;
use App\Models\User;
use App\Models\CoinTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
 
class ScoreboardController extends Controller
{
    public function getPersonalStats(Request $request)
    {
        $userId = Auth::id();
 
        if (!$userId) {
            return response()->json(['error' => 'Utilizador não autenticado'], 401);
        }
 
        // --- MATCHES (PARTIDAS) ---
        $totalMatches = GameMatch::where('status', 'Ended')
            ->where(function($query) use ($userId) {
                $query->where('player1_user_id', $userId)
                      ->orWhere('player2_user_id', $userId);
            })->count();
 
        // Vitórias em Partidas (Simplificado: confia no winner_user_id)
        $winsMatches = GameMatch::where('status', 'Ended')
            ->where('winner_user_id', $userId)
            ->count();
 
        // Tempo Total (Baseado na soma dos JOGOS individuais)
        $totalTime = Game::where('status', 'Ended')
            ->where(function($query) use ($userId) {
                $query->where('player1_user_id', $userId)
                      ->orWhere('player2_user_id', $userId);
            })->sum('total_time');
 
        // --- GAMES (JOGOS INDIVIDUAIS) ---
        $totalGames = Game::where('status', 'Ended')
            ->where(function($query) use ($userId) {
                $query->where('player1_user_id', $userId)
                      ->orWhere('player2_user_id', $userId);
            })->count();
 
        $winsGames = Game::where('status', 'Ended')
            ->where('winner_user_id', $userId)
            ->count();
 
        // Pontos (Apenas de jogos terminados)
        $pointsP1 = Game::where('status', 'Ended')
            ->where('player1_user_id', $userId)
            ->sum('player1_points');
 
        $pointsP2 = Game::where('status', 'Ended')
            ->where('player2_user_id', $userId)
            ->where('player1_user_id', '!=', $userId)
            ->sum('player2_points');
 
        $totalPoints = $pointsP1 + $pointsP2;
 
        // --- ESTATÍSTICAS ESPECÍFICAS ---
        $draws = Game::where('status', 'Ended')
            ->where('is_draw', 1)
            ->where(function($query) use ($userId) {
                $query->where('player1_user_id', $userId)
                      ->orWhere('player2_user_id', $userId);
            })->count();
 
        $riscas = Game::where('status', 'Ended')
            ->where(function($masterQuery) use ($userId) {
                $masterQuery->where(function ($query) use ($userId) {
                    $query->where('player1_user_id', $userId)->whereBetween('player1_points', [61, 89]);
                })->orWhere(function ($query) use ($userId) {
                    $query->where('player2_user_id', $userId)
                          ->where('player1_user_id', '!=', $userId)
                          ->whereBetween('player2_points', [61, 89]);
                });
            })->count();
 
        $capotes = Game::where('status', 'Ended')
            ->where(function($masterQuery) use ($userId) {
                $masterQuery->where(function ($query) use ($userId) {
                    $query->where('player1_user_id', $userId)->whereBetween('player1_points', [90, 119]);
                })->orWhere(function ($query) use ($userId) {
                    $query->where('player2_user_id', $userId)
                          ->where('player1_user_id', '!=', $userId)
                          ->whereBetween('player2_points', [90, 119]);
                });
            })->count();
 
        $bandeiras = Game::where('status', 'Ended')
            ->where(function($masterQuery) use ($userId) {
                $masterQuery->where(function ($query) use ($userId) {
                    $query->where('player1_user_id', $userId)->where('player1_points', 120);
                })->orWhere(function ($query) use ($userId) {
                    $query->where('player2_user_id', $userId)
                          ->where('player1_user_id', '!=', $userId)
                          ->where('player2_points', 120);
                });
            })->count();
 
        return response()->json([
            'totalMatches' => $totalMatches,
            'winsMatches'  => $winsMatches,
            'totalTime'    => $totalTime,
            'totalGames'   => $totalGames,
            'winsGames'    => $winsGames,
            'totalPoints'  => $totalPoints,
            'draws'        => $draws,
            'riscas'       => $riscas,
            'capotes'      => $capotes,
            'bandeiras'    => $bandeiras,
        ]);
    }
 
    public function getGlobalRankings(Request $request)
    {
        try {
            $type = $request->query('type', 'wins');
            $search = $request->query('search');
            $order = $request->query('order', 'desc');
 
            $query = null;
 
            switch ($type) {
                case 'coins':
                    $query = CoinTransaction::where('coin_transaction_type_id', 6)
                        ->join('users', 'coin_transactions.user_id', '=', 'users.id')
                        ->select('users.nickname as name', DB::raw('SUM(coins) as value'))
                        ->groupBy('users.nickname');
                    break;
 
                case 'capotes':
                    $query = Game::where('status', 'Ended')
                        ->whereBetween('player1_points', [91, 119])
                        ->join('users', 'games.player1_user_id', '=', 'users.id')
                        ->select('users.nickname as name', DB::raw('count(*) as value'))
                        ->groupBy('users.nickname');
                    break;
 
                case 'bandeiras':
                    $query = Game::where('status', 'Ended')
                        ->where('player1_points', 120)
                        ->join('users', 'games.player1_user_id', '=', 'users.id')
                        ->select('users.nickname as name', DB::raw('count(*) as value'))
                        ->groupBy('users.nickname');
                    break;
 
                case 'wins':
                default:
                    $query = GameMatch::where('status', 'Ended')
                        ->whereNotNull('winner_user_id')
                        ->join('users', 'matches.winner_user_id', '=', 'users.id')
                        ->select('users.nickname as name', DB::raw('count(*) as value'))
                        ->groupBy('users.nickname');
                    break;
            }
 
            if ($search) {
                $query->where('users.nickname', 'like', "%{$search}%");
            }
 
            $query->orderBy('value', $order);
 
            $results = $query->get();
 
            // 2. Definir página e tamanho
            $page = $request->input('page', 1);
            $perPage = 20; // 20 itens por página como pedido
            $offset = ($page * $perPage) - $perPage;
 
            // 3. Fatiar a coleção para a página atual
            $itemsForCurrentPage = $results->slice($offset, $perPage)->values();
 
            // 4. Criar o objeto Paginator manualmente
            $data = new LengthAwarePaginator(
                $itemsForCurrentPage,
                $results->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
 
            return response()->json([
                'data' => $data,
                'type' => $type
            ]);
 
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro SQL: ' . $e->getMessage()
            ], 500);
        }
    }
}
 