import re

def fix_migration():
    filepath = 'Modules/Core/database/migrations/2026_05_16_143257_create_users_table.php'
    with open(filepath, 'r') as f:
        content = f.read()

    # Wrap the table creation in a check
    old_up = """    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->timestamps();
        });
    }"""

    new_up = """    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }
    }"""

    content = content.replace(old_up, new_up)

    with open(filepath, 'w') as f:
        f.write(content)

fix_migration()
