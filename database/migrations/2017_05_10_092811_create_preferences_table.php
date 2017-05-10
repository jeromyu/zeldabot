<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePreferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('preferences', function(Blueprint $table){
            $table->unsignedInteger('tag_id');
            $table->unsignedInteger('user_id');
            $table->timestamps();

            $table->primary(['tag_id', 'user_id'], 'preferences_tag_id_user_id_primary');

            $table->foreign('tag_id')
                  ->references('id')
                  ->on('tags')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
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
        Schema::table('preferences', function(Blueprint $table){
            $table->dropForeign(['tag_id']);
            $table->dropForeign(['user_id']);
            $table->dropPrimary('preferences_tag_id_user_id_primary');
        });

        Schema::dropIfExists('preferences');
    }
}
