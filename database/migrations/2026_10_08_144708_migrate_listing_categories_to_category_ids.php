<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Category;
use App\Models\Listing;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    $listings = Listing::whereNotNull('category')->get();

    foreach ($listings as $listing) {
        $category = Category::where('name', $listing->category)->first();

        if ($category) {
            $listing->category_id = $category->id;
            $listing->save();
        }
    }
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            //
        });
    }
};
