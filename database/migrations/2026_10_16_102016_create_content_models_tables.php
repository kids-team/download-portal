<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->json('synonyms')->nullable();
            $table->timestamps();
        });

        Schema::create('audiences', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('byte_size');
            $table->string('checksum', 64)->nullable();
            $table->string('visibility')->default('public');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->timestamps();

            $table->unique(['disk', 'path']);
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('documents')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('audience_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cover_media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('slug');
            $table->unsignedInteger('position')->default(0);
            $table->string('title');
            $table->json('content')->nullable();
            $table->text('summary')->nullable();
            $table->string('label')->nullable();
            $table->string('external_url')->nullable();
            $table->string('icon')->nullable();
            $table->string('copyright_notice')->nullable();
            $table->boolean('is_listed')->default(true);
            $table->boolean('is_locked')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['parent_id', 'position']);
            $table->index('published_at');
        });

        $documentSlugIndex = 'documents_parent_slug_unique';

        if (DB::getDriverName() === 'mysql') {
            DB::statement("CREATE UNIQUE INDEX {$documentSlugIndex} ON documents ((COALESCE(parent_id, 0)), slug)");
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER documents_prevent_parent_cycle
                BEFORE UPDATE ON documents
                FOR EACH ROW
                BEGIN
                    DECLARE cycle_exists BOOLEAN DEFAULT FALSE;

                    IF NEW.parent_id IS NOT NULL THEN
                        WITH RECURSIVE ancestors (id) AS (
                            SELECT NEW.parent_id
                            UNION DISTINCT
                            SELECT documents.parent_id
                            FROM documents
                            INNER JOIN ancestors ON documents.id = ancestors.id
                            WHERE documents.parent_id IS NOT NULL
                        )
                        SELECT EXISTS(SELECT 1 FROM ancestors WHERE id = NEW.id) INTO cycle_exists;

                        IF cycle_exists THEN
                            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Document hierarchy cannot contain cycles';
                        END IF;
                    END IF;
                END
                SQL);
        } else {
            DB::statement("CREATE UNIQUE INDEX {$documentSlugIndex} ON documents (COALESCE(parent_id, 0), slug)");
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER documents_prevent_parent_cycle
                BEFORE UPDATE OF parent_id ON documents
                FOR EACH ROW
                WHEN NEW.parent_id IS NOT NULL
                BEGIN
                    SELECT RAISE(ABORT, 'Document hierarchy cannot contain cycles')
                    WHERE EXISTS (
                        WITH RECURSIVE ancestors (id) AS (
                            SELECT NEW.parent_id
                            UNION
                            SELECT documents.parent_id
                            FROM documents
                            INNER JOIN ancestors ON documents.id = ancestors.id
                            WHERE documents.parent_id IS NOT NULL
                        )
                        SELECT 1 FROM ancestors WHERE id = NEW.id
                    );
                END
                SQL);
        }

        Schema::create('document_tag', function (Blueprint $table) {
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();

            $table->primary(['document_id', 'tag_id']);
        });

        Schema::create('downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('byte_size');
            $table->string('checksum', 64)->nullable();
            $table->string('visibility')->default('public');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_published')->default(false);
            $table->unsignedBigInteger('download_count')->default(0);
            $table->timestamp('last_downloaded_at')->nullable();
            $table->timestamps();

            $table->unique(['disk', 'path']);
            $table->index(['document_id', 'position']);
        });

        Schema::create('download_daily_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('download_id')->constrained()->cascadeOnDelete();
            $table->date('downloaded_on');
            $table->unsignedBigInteger('download_count')->default(0);
            $table->timestamps();

            $table->unique(['download_id', 'downloaded_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('download_daily_statistics');
        Schema::dropIfExists('downloads');
        Schema::dropIfExists('document_tag');
        DB::statement('DROP TRIGGER IF EXISTS documents_prevent_parent_cycle');
        if (DB::getDriverName() === 'mysql') {
            DB::statement('DROP INDEX documents_parent_slug_unique ON documents');
        } else {
            DB::statement('DROP INDEX IF EXISTS documents_parent_slug_unique');
        }
        Schema::dropIfExists('documents');
        Schema::dropIfExists('media_assets');
        Schema::dropIfExists('audiences');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('tags');
    }
};
