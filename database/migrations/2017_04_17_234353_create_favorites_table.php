<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFavoritesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('favorites', function(Blueprint $table){
            $table->unsignedInteger('link_id');
            $table->unsignedInteger('user_id');
            $table->timestamps();

            $table->primary(['link_id', 'user_id'], 'favorites_link_id_user_id_primary');

            $table->foreign('link_id')
                  ->references('id')
                  ->on('links')
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
        Schema::table('favorites', function(Blueprint $table){
            $table->dropForeign(['link_id']);
            $table->dropForeign(['user_id']);
            $table->dropPrimary('favorites_link_id_user_id_primary');
        });

        Schema::dropIfExists('favorites');
    }
}
