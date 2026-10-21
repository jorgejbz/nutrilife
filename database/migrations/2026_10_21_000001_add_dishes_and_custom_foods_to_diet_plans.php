<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Platillos y alimentos personalizados (unificación con la base secundaria).
 *
 * - diet_plan_dishes: platillos reutilizables. user_id NULL = platillo del
 *   sistema (lo ven todos); con valor = platillo propio del nutriólogo.
 * - diet_plan_dish_items: ingredientes del platillo, del catálogo `foods`
 *   o personalizados (custom_*).
 * - diet_plan_items: food_id pasa a ser opcional (único cambio no aditivo;
 *   los renglones existentes no cambian), columnas custom_* para alimentos
 *   fuera del catálogo y diet_plan_dish_id = platillo del que salió el
 *   renglón (SET NULL si se borra el platillo: el plan conserva su copia).
 * - CHECK (solo MySQL/MariaDB): cada renglón es del catálogo o
 *   personalizado, nunca ambos ni ninguno.
 *
 * custom_kcal y macros son el aporte TOTAL del renglón para sus `grams`
 * (mismo criterio que la base secundaria). Precisión igual a `foods`:
 * kcal DECIMAL(7,2), macros y gramos DECIMAL(6,2).
 *
 * Equivalente en SQL: database/sql/actualizacion_v3_2.sql
 */
return new class extends Migration
{
    private const CUSTOM_COLUMNS = [
        'custom_name',
        'custom_kcal',
        'custom_protein_g',
        'custom_carbs_g',
        'custom_lipids_g',
        'custom_portion_description',
        'custom_image_url',
    ];

    private const ITEMS_CHECK = 'diet_plan_items_food_or_custom_check';

    private const DISH_ITEMS_CHECK = 'diet_plan_dish_items_food_or_custom_check';

    public function up(): void
    {
        if (! Schema::hasTable('diet_plan_dishes')) {
            Schema::create('diet_plan_dishes', function (Blueprint $table) {
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_general_ci'); // igual que proyecto_v3.sql

                $table->id();
                $table->foreignId('user_id')->nullable()
                    ->comment('NULL = platillo del sistema; con valor = platillo propio del nutriólogo')
                    ->constrained('users')->cascadeOnDelete();
                $table->string('name');
                $table->string('image_url', 500)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('diet_plan_dish_items')) {
            Schema::create('diet_plan_dish_items', function (Blueprint $table) {
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_general_ci');

                $table->id();
                $table->foreignId('diet_plan_dish_id')->constrained('diet_plan_dishes')->cascadeOnDelete();
                $table->foreignId('food_id')->nullable()->constrained('foods');
                $table->string('custom_name')->nullable();
                $table->decimal('custom_kcal', 7, 2)->nullable();
                $table->decimal('custom_protein_g', 6, 2)->nullable();
                $table->decimal('custom_carbs_g', 6, 2)->nullable();
                $table->decimal('custom_lipids_g', 6, 2)->nullable();
                $table->decimal('grams', 6, 2)->default(100);
                $table->timestamps();
            });
        }

        // Separado de los ADD: en SQLite change() reconstruye la tabla.
        Schema::table('diet_plan_items', function (Blueprint $table) {
            $table->unsignedBigInteger('food_id')->nullable()->change();
        });

        Schema::table('diet_plan_items', function (Blueprint $table) {
            if (! Schema::hasColumn('diet_plan_items', 'diet_plan_dish_id')) {
                $table->foreignId('diet_plan_dish_id')->nullable()->after('diet_plan_meal_id')
                    ->comment('Platillo del que salió el renglón (los valores se copian)')
                    ->constrained('diet_plan_dishes')->nullOnDelete();
            }

            if (! Schema::hasColumn('diet_plan_items', 'custom_name')) {
                $table->string('custom_name')->nullable()->after('food_id')
                    ->comment('Alimento fuera del catálogo (food_id NULL)');
            }

            if (! Schema::hasColumn('diet_plan_items', 'custom_kcal')) {
                $table->decimal('custom_kcal', 7, 2)->nullable()->after('custom_name')
                    ->comment('Aporte total del renglón');
            }

            if (! Schema::hasColumn('diet_plan_items', 'custom_protein_g')) {
                $table->decimal('custom_protein_g', 6, 2)->nullable()->after('custom_kcal');
            }

            if (! Schema::hasColumn('diet_plan_items', 'custom_carbs_g')) {
                $table->decimal('custom_carbs_g', 6, 2)->nullable()->after('custom_protein_g');
            }

            if (! Schema::hasColumn('diet_plan_items', 'custom_lipids_g')) {
                $table->decimal('custom_lipids_g', 6, 2)->nullable()->after('custom_carbs_g');
            }

            if (! Schema::hasColumn('diet_plan_items', 'custom_portion_description')) {
                $table->string('custom_portion_description')->nullable()->after('custom_lipids_g');
            }

            if (! Schema::hasColumn('diet_plan_items', 'custom_image_url')) {
                $table->string('custom_image_url', 500)->nullable()->after('custom_portion_description');
            }
        });

        $this->addFoodOrCustomCheck('diet_plan_items', self::ITEMS_CHECK);
        $this->addFoodOrCustomCheck('diet_plan_dish_items', self::DISH_ITEMS_CHECK);
    }

    public function down(): void
    {
        // food_id vuelve a NOT NULL: no se borran datos clínicos en silencio.
        $custom = DB::table('diet_plan_items')->whereNull('food_id')->count();

        if ($custom > 0) {
            throw new RuntimeException(
                "No se puede revertir: hay {$custom} alimentos personalizados en planes (food_id NULL). Respáldalos o bórralos primero."
            );
        }

        if ($this->supportsChecks() && $this->constraintExists('diet_plan_items', self::ITEMS_CHECK)) {
            DB::statement('ALTER TABLE `diet_plan_items` DROP CONSTRAINT `'.self::ITEMS_CHECK.'`');
        }

        Schema::table('diet_plan_items', function (Blueprint $table) {
            if (Schema::hasColumn('diet_plan_items', 'diet_plan_dish_id')) {
                $table->dropConstrainedForeignId('diet_plan_dish_id');
            }

            $table->dropColumn(array_values(array_filter(
                self::CUSTOM_COLUMNS,
                fn (string $column) => Schema::hasColumn('diet_plan_items', $column)
            )));
        });

        Schema::table('diet_plan_items', function (Blueprint $table) {
            $table->unsignedBigInteger('food_id')->nullable(false)->change();
        });

        Schema::dropIfExists('diet_plan_dish_items');
        Schema::dropIfExists('diet_plan_dishes');
    }

    /** Del catálogo (food_id, sin custom_*) o personalizado (custom_name, sin food_id). */
    private function addFoodOrCustomCheck(string $table, string $name): void
    {
        if (! $this->supportsChecks() || $this->constraintExists($table, $name)) {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$name}` CHECK (
            (`food_id` IS NOT NULL AND `custom_name` IS NULL AND `custom_kcal` IS NULL
                AND `custom_protein_g` IS NULL AND `custom_carbs_g` IS NULL AND `custom_lipids_g` IS NULL)
            OR (`food_id` IS NULL AND `custom_name` IS NOT NULL)
        )");
    }

    /** Las pruebas usan SQLite en memoria: ahí la regla la cuida la validación. */
    private function supportsChecks(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function constraintExists(string $table, string $name): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $name)
            ->exists();
    }
};
