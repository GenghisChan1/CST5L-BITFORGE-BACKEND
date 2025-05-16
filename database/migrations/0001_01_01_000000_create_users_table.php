<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('userstable', function (Blueprint $table) {
            $table->string('id')->primary(); // User ID (e.g., 'u1')
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone_number');
            $table->string('street')->nullable();
            $table->string('city');
            $table->string('region');
            $table->string('postal_code');
            $table->string('profile_picture')->nullable();
            $table->enum('role', ['user', 'admin'])->default('user');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('image_url');
            $table->string('item_name');
            $table->text('tags'); // "Gaming, Desktop, High-end"
            $table->string('warehouse');
            $table->decimal('price', 10, 2);
            $table->integer('stocks')->default(0);
            $table->integer('reviews_count')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0.00);

            // Specs (JSON-formatted fields that can hold null values)
            $table->json('processor')->nullable()->default(json_encode([
                'brand' => null,
                'model' => null,
                'cores' => null,
                'threads' => null,
                'base_clock' => null,
                'boost_clock' => null,
            ]));

            $table->json('graphics_card')->nullable()->default(json_encode([
                'brand' => null,
                'model' => null,
                'vram' => null,
                'clock_speed' => null,
            ]));

            $table->json('ram')->nullable()->default(json_encode([
                'type' => null,
                'capacity' => null,
                'speed' => null,
            ]));

            $table->json('storage')->nullable()->default(json_encode([
                'type' => null,
                'capacity' => null,
            ]));

            $table->json('motherboard')->nullable()->default(json_encode([
                'chipset' => null,
                'form_factor' => null,
                'socket' => null,
            ]));

            $table->json('display')->nullable()->default(json_encode([
                'size' => null,
                'resolution' => null,
                'panel_type' => null,
                'refresh_rate' => null,
            ]));

            $table->json('battery')->nullable()->default(json_encode([
                'capacity' => null,
                'life' => null,
            ]));

            $table->json('keyboard')->nullable()->default(json_encode([
                'type' => null,
                'backlit' => null,
            ]));

            $table->json('mouse')->nullable()->default(json_encode([
                'type' => null,
                'dpi' => null,
            ]));

            $table->json('microphone')->nullable()->default(json_encode([
                'type' => null,
                'pattern' => null,
            ]));

            $table->json('headset')->nullable()->default(json_encode([
                'driver_size' => null,
            ]));

            // Array-form specs as comma-separated strings
            $table->string('ports')->nullable(); // "USB 3.2, USB-C, HDMI, DisplayPort, Ethernet"
            $table->string('connectivity')->nullable(); // "WiFi 6E, Bluetooth 5.3"

            $table->string('operating_system')->nullable();
            $table->string('power_supply')->nullable();
            $table->string('cooling')->nullable();
            $table->string('dimensions')->nullable();
            $table->string('weight')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('userstable');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('items');
    }
};
