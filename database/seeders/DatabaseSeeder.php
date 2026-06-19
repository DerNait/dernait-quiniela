<?php

namespace Database\Seeders;

use App\Enums\ScoringCategory;
use App\Models\Quiniela;
use App\Models\QuinielaResult;
use App\Models\ScoringRule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // The party host / admin. Credentials come from the environment so the
        // production admin password is never the hard-coded default.
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@quiniela.test')],
            [
                'name' => env('ADMIN_NAME', 'Anfitrión'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'is_admin' => true,
            ],
        );

        // Demo guests so the leaderboard isn't empty in dev — never in production.
        if (! app()->environment('production')) {
            foreach (['Ana', 'Beto', 'Caro'] as $i => $name) {
                User::updateOrCreate(
                    ['email' => strtolower($name).'@quiniela.test'],
                    ['name' => $name, 'password' => Hash::make('password'), 'is_admin' => false],
                );
            }
        }

        $this->createQuiniela(
            name: 'Alemania vs Costa de Marfil',
            home: 'Alemania', away: 'Costa de Marfil',
            homeFlag: '🇩🇪', awayFlag: '🇨🇮',
            kickoff: '2026-06-20 15:00:00',
            homePlayers: ['Kimmich', 'Musiala', 'Wirtz', 'Havertz', 'Füllkrug', 'Sané', 'Gnabry', 'Rüdiger'],
            awayPlayers: ['Haller', 'Pépé', 'Kessié', 'Gradel', 'Boly', 'Seri', 'Diallo', 'Krasso'],
        );

        $this->createQuiniela(
            name: 'Ecuador vs Curazao',
            home: 'Ecuador', away: 'Curazao',
            homeFlag: '🇪🇨', awayFlag: '🇨🇼',
            kickoff: '2026-06-20 18:00:00',
            homePlayers: ['E. Valencia', 'Caicedo', 'Plata', 'Sarmiento', 'Estupiñán', 'Hincapié', 'Rodríguez', 'Mena'],
            awayPlayers: ['Janga', 'L. Bacuna', 'Bonevacia', 'Antonia', 'Martina', 'Sambo', 'Kingsley', 'Dumfries'],
        );
    }

    private function createQuiniela(
        string $name, string $home, string $away,
        string $homeFlag, string $awayFlag, string $kickoff,
        array $homePlayers, array $awayPlayers,
    ): void {
        $quiniela = Quiniela::updateOrCreate(
            ['name' => $name],
            [
                'home_team' => $home, 'away_team' => $away,
                'home_flag' => $homeFlag, 'away_flag' => $awayFlag,
                'kickoff_at' => $kickoff, 'status' => 'scheduled',
            ],
        );

        // Points table.
        foreach (ScoringCategory::cases() as $category) {
            ScoringRule::updateOrCreate(
                ['quiniela_id' => $quiniela->id, 'category' => $category->value],
                ['points' => $category->defaultPoints(), 'enabled' => true],
            );
        }

        QuinielaResult::firstOrCreate(['quiniela_id' => $quiniela->id]);

        // Rosters are only seeded once: re-running the seeder on an existing
        // quiniela must not wipe players that predictions already point to.
        if ($quiniela->players()->exists()) {
            return;
        }

        foreach ($homePlayers as $i => $playerName) {
            $quiniela->players()->create(['team' => 'home', 'name' => $playerName, 'number' => $i + 1]);
        }
        foreach ($awayPlayers as $i => $playerName) {
            $quiniela->players()->create(['team' => 'away', 'name' => $playerName, 'number' => $i + 1]);
        }
        // Own-goal options for the "first scorer" dropdown.
        $quiniela->players()->create(['team' => 'home', 'name' => 'Autogol ('.$home.')', 'kind' => 'own_goal']);
        $quiniela->players()->create(['team' => 'away', 'name' => 'Autogol ('.$away.')', 'kind' => 'own_goal']);
    }
}
