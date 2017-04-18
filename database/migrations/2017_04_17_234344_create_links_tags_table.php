<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLinksTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('links_tags', function(Blueprint $table){
            $table->unsignedInteger('link_id');
            $table->unsignedInteger('tag_id');

            $table->primary(['link_id', 'tag_id'], 'links_tags_link_id_tag_id_primary');

            $table->foreign('link_id')
                  ->references('id')
                  ->on('links')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->foreign('tag_id')
                  ->references('id')
                  ->on('tags')
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
        Schema::table('links_tags', function(Blueprint $table){
            $table->dropForeign(['link_id']);
            $table->dropForeign(['tag_id']);
            $table->dropPrimary('links_tags_link_id_tag_id_primary');
        });

        Schema::dropIfExists('links_tags');
    }
}
