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

        // Full 26-man World Cup 2026 squads (Group E), per official call-ups.
        $this->createQuiniela(
            name: 'Alemania vs Costa de Marfil',
            home: 'Alemania', away: 'Costa de Marfil',
            homeFlag: '🇩🇪', awayFlag: '🇨🇮',
            kickoff: '2026-06-20 15:00:00',
            homePlayers: [
                'Oliver Baumann', 'Manuel Neuer', 'Alexander Nübel',
                'Waldemar Anton', 'Nathaniel Brown', 'Pascal Groß', 'Joshua Kimmich',
                'Aleksandar Pavlović', 'David Raum', 'Antonio Rüdiger', 'Nico Schlotterbeck',
                'Jonathan Tah', 'Malick Thiaw',
                'Nadiem Amiri', 'Leon Goretzka', 'Lennart Karl', 'Felix Nmecha', 'Angelo Stiller',
                'Maximilian Beier', 'Kai Havertz', 'Jamie Leweling', 'Jamal Musiala',
                'Leroy Sané', 'Deniz Undav', 'Florian Wirtz', 'Nick Woltemade',
            ],
            awayPlayers: [
                'Yahia Fofana', 'Mohamed Koné', 'Alban Lafont',
                'Emmanuel Agbadou', 'Clément Akpa', 'Ousmane Diomandé', 'Guéla Doué',
                'Ghislain Konan', 'Odilon Kossounou', 'Evan Ndicka', 'Wilfried Singo',
                'Seko Fofana', 'Parfait Guiagon', 'Christ Inao Oulaï', 'Franck Kessié',
                'Ibrahim Sangaré', 'Jean-Michaël Seri',
                'Simon Adingra', 'Ange-Yoan Bonny', 'Amad Diallo', 'Oumar Diakité',
                'Yan Diomandé', 'Evann Guessand', 'Nicolas Pépé', 'Bazoumana Touré', 'Elye Wahi',
            ],
        );

        $this->createQuiniela(
            name: 'Ecuador vs Curazao',
            home: 'Ecuador', away: 'Curazao',
            homeFlag: '🇪🇨', awayFlag: '🇨🇼',
            kickoff: '2026-06-20 18:00:00',
            homePlayers: [
                'Gonzalo Valle', 'Hernán Galíndez', 'Moisés Ramírez',
                'Piero Hincapié', 'Willian Pacho', 'Angelo Preciado', 'Félix Torres',
                'Jackson Porozo', 'Joel Ordóñez', 'Pervis Estupiñán', 'Yaimar Medina',
                'Alan Franco', 'Moisés Caicedo', 'Pedro Vite', 'Kendry Páez', 'Jordy Alcívar',
                'Denil Castillo', 'Anthony Valencia', 'Alan Minda',
                'Enner Valencia', 'Kevin Rodríguez', 'Gonzalo Plata', 'John Yeboah',
                'Nilson Angulo', 'Jordy Caicedo', 'Jeremy Arévalo',
            ],
            awayPlayers: [
                'Eloy Room', 'Trevor Doornbusch', 'Tyrick Bodak',
                'Armando Obispo', 'Deveron Fonville', 'Joshua Brenet', 'Jurien Gaari',
                'Riechedly Bazoer', 'Roshon van Eijma', 'Sherel Floranus', 'Shurandy Sambo',
                'Leandro Bacuna', 'Juninho Bacuna', 'Livano Comenencia', 'Kevin Felida',
                "Ar'Jany Martha", 'Tyrese Noslin', 'Godfried Roemeratoe',
                'Jeremy Antonisse', 'Tahith Chong', 'Kenji Gorré', 'Sontje Hansen',
                'Gervane Kastaneer', 'Brandley Kuwas', 'Jurgen Locadia', 'Jearl Margaritha',
            ],
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

        // Real jersey numbers aren't loaded, so we leave them null (the dropdown
        // just shows the name).
        foreach ($homePlayers as $playerName) {
            $quiniela->players()->create(['team' => 'home', 'name' => $playerName, 'number' => null]);
        }
        foreach ($awayPlayers as $playerName) {
            $quiniela->players()->create(['team' => 'away', 'name' => $playerName, 'number' => null]);
        }
        // Own-goal options for the "first scorer" dropdown.
        $quiniela->players()->create(['team' => 'home', 'name' => 'Autogol ('.$home.')', 'kind' => 'own_goal']);
        $quiniela->players()->create(['team' => 'away', 'name' => 'Autogol ('.$away.')', 'kind' => 'own_goal']);
    }
}
