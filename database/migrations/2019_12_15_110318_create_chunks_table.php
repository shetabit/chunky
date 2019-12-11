<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChunksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chunks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('size')->nullable();
            $table->string('offset')->nullable();
            $table->longText('meta');
            $table->uuid('file_id')->index();
            $table->timestamps();
            $table->softDeletes();

            $table
                ->foreign('file_id')
                ->references('id')
                ->on('files')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chunks');
    }
}
