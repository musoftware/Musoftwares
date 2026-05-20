<?php
/*
 * Created At: 2026-05-16T14:23:44Z
 */

namespace Modules\Freelance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Freelance\Models\Skill;
use Modules\Freelance\Models\PointPackage;

class FreelanceDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default Skills
        $skills = [
            ['name' => 'PHP', 'description' => 'Server-side scripting language'],
            ['name' => 'Laravel', 'description' => 'The PHP Framework for Web Artisans'],
            ['name' => 'React', 'description' => 'A JavaScript library for building user interfaces'],
            ['name' => 'TypeScript', 'description' => 'Typed superset of JavaScript'],
            ['name' => 'Tailwind CSS', 'description' => 'A utility-first CSS framework'],
            ['name' => 'Playwright', 'description' => 'End-to-end testing for modern web apps'],
            ['name' => 'Node.js', 'description' => 'JavaScript runtime built on Chrome\'s V8'],
            ['name' => 'MySQL', 'description' => 'Open-source relational database management system'],
        ];

        foreach ($skills as $skill) {
            Skill::firstOrCreate(['name' => $skill['name']], $skill);
        }

        // Default Point Packages (1 Point = 1 EGP)
        $packages = [
            ['name' => 'Basic Pack', 'points' => 50, 'price' => 50.00, 'currency_code' => 'EGP'],
            ['name' => 'Standard Pack', 'points' => 120, 'price' => 120.00, 'currency_code' => 'EGP'],
            ['name' => 'Pro Pack', 'points' => 300, 'price' => 300.00, 'currency_code' => 'EGP'],
            ['name' => 'Enterprise Pack', 'points' => 1000, 'price' => 1000.00, 'currency_code' => 'EGP'],
        ];

        foreach ($packages as $package) {
            PointPackage::firstOrCreate(['name' => $package['name']], $package);
        }
    }
}
