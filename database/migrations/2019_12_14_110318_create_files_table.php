<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            $table->uuid('id')->primary();

            /**
             * file informations on client
             */
            $table->string('client_name')->index();
            $table->string('client_extension');

            /**
             * file informations on server
             */
            $table->string('name')->nullable()->index();
            $table->string('extension')->nullable()->index();

            $table->integer('size');
            $table->text('path')->nullable();

            $table->text('meta')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('files');
    }
}
