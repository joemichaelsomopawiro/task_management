<?php

       use Illuminate\Database\Migrations\Migration;
       use Illuminate\Database\Schema\Blueprint;
       use Illuminate\Support\Facades\Schema;

       class AlterPersonalAccessTokensTable extends Migration
       {
           public function up(): void
           {
               Schema::table('personal_access_tokens', function (Blueprint $table) {
                   $table->string('tokenable_id')->change()->index();
               });
           }

           public function down(): void
           {
               Schema::table('personal_access_tokens', function (Blueprint $table) {
                   $table->unsignedBigInteger('tokenable_id')->change()->index();
               });
           }
       }