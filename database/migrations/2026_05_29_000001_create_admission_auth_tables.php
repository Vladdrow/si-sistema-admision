<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('persona')) {
            Schema::create('persona', function (Blueprint $table): void {
                $table->id('id_persona');
                $table->string('ci', 20)->unique();
                $table->string('nombres', 50);
                $table->string('apellido_paterno', 50);
                $table->string('apellido_materno', 50)->nullable();
                $table->date('fecha_nacimiento');
                $table->char('sexo', 1);
                $table->string('direccion', 70)->nullable();
                $table->string('telefono', 20)->nullable();
                $table->string('correo', 50)->unique();
            });
        }

        if (! Schema::hasTable('credencial')) {
            Schema::create('credencial', function (Blueprint $table): void {
                $table->id('id_credencial');
                $table->string('registro', 15)->unique();
                $table->string('contrasena', 255);
                $table->string('rol', 50);
                $table->boolean('estado')->default(true);
                $table->timestamp('fecha_ultimo_acceso')->nullable();
                $table->integer('intentos_fallidos')->default(0);
                $table->timestamp('fecha_bloqueo')->nullable();
                $table->string('codigo_recuperacion', 10)->nullable();
                $table->timestamp('fecha_expiracion_codigo')->nullable();
                $table->foreignId('id_persona')->unique()->constrained('persona', 'id_persona')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('bitacora')) {
            Schema::create('bitacora', function (Blueprint $table): void {
                $table->id('id_bitacora');
                $table->timestamp('fecha_hora')->useCurrent();
                $table->string('accion', 50);
                $table->string('modulo', 50);
                $table->text('descripcion')->nullable();
                $table->string('ip_origen', 45)->nullable();
                $table->foreignId('id_persona')->constrained('persona', 'id_persona')->cascadeOnDelete();
            });
        }

        /* if (DB::table('credencial')->count() === 0) {
            $idPersona = DB::table('persona')->insertGetId([
                'ci' => '12345678',
                'nombres' => 'Administrador',
                'apellido_paterno' => 'Sistema',
                'apellido_materno' => null,
                'fecha_nacimiento' => '1990-01-01',
                'sexo' => 'M',
                'direccion' => 'FICCT',
                'telefono' => null,
                'correo' => 'admin@sistema-admision.test',
            ], 'id_persona');

            DB::table('credencial')->insert([
                'registro' => 'admin',
                'contrasena' => Hash::make('admin12345'),
                'rol' => 'Administrador',
                'estado' => true,
                'intentos_fallidos' => 0,
                'id_persona' => $idPersona,
            ]);
        } */
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora');
        Schema::dropIfExists('credencial');
        Schema::dropIfExists('persona');
    }
};
