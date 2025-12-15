<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('tracking_number');
            $table->string('courier_code');

            $table->string('trackingmore_id')->nullable();
            $table->string('delivery_status')->nullable();
            $table->string('latest_event')->nullable();
            $table->timestamp('latest_checkpoint_time')->nullable();

            $table->json('raw')->nullable();

            $table->timestamps();

            $table->index(['tracking_number', 'courier_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
