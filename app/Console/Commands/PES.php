<?php

namespace App\Console\Commands;

use FilesystemIterator;
use Google_Service_Sheets;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use PulkitJalan\Google\Facades\Google;

class PES extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pes:results {--from_row=} {--to_row=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add new results';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $from_row = $this->argument('from_row');
        $to_row = $this->argument('to_row');
        if (empty($from_row) || empty($to_row)) {
            $this->error("Bad parameters. You need to set --from_row and --to_row");
        }
        \Illuminate\Support\Facades\DB::table('games')->truncate();
        $client = Google::getClient();
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS_READONLY);
        $sheets = new \Google_Service_Sheets($client);
        $spreadsheetId = '1UkLLs2C_OstNisLFB0PhhnsHu9WtnEJC7dJa2SctKlc';
        $range = "martes!B{$from_row}:F{$to_row}";
        $response = $sheets->spreadsheets_values->get($spreadsheetId, $range);
        $rows = $response->getValues();
        $this->createGames($rows);
    }

    protected function createGames($rows)
    {
        $date = \Carbon\Carbon::now();
        foreach ($rows as $row) {
            $date = empty($row[0]) ? $date : \Carbon\Carbon::createFromFormat('Y-m-d', $row[0]);
            $players = explode('/', $row[1]);
            sort($players);
            $home = \App\Models\Team::where('name', "{$players[0]}-{$players[1]}")->first();
            $players = explode('/', $row[4]);
            sort($players);
            $away = \App\Models\Team::where('name', "{$players[0]}-{$players[1]}")->first();
            if(empty($home) || empty($away)) {
                $this->error("ERROR", $row);
            }
            try {
                $game = \App\Models\Game::create([
                    'team_home_id' => $home->id,
                    'team_away_id' => $away->id,
                    'team_home_score' => $row[2],
                    'team_away_score'=> $row[3],
                    'result'=> $row[2] >= $row[3] ? ($row[2] > $row[3] ? 'home' : 'draw')  : 'away',
                    'created_at'=> $date->format('Y-m-d H:i:s'),
                    'updated_at'=> $date->format('Y-m-d H:i:s'),
                ]);
            }
            catch (\Exception $e) {
                dd($row);
            }
            $this->info("Game created ID: {$game->id}");
        }
    }
}
