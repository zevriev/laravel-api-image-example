<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageTest extends TestCase
{
    /**
     * An image list test.
     *
     * @return void
     */
    public function testIndex() {
        $response = $this->get('/api/v1/images');

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Retrieved successfully',
                ]);
    }

    /**
     * An image upload multipart test
     * @return void
     */
    public function testStore() {
        Storage::fake('images');

        $response = $this->json('POST', 'api/v1/images', [
            'fileNames' => [UploadedFile::fake()->image('photo1.jpg', 600, 600),
                            UploadedFile::fake()->image('photo2.jpg', 600, 600)]
        ]);

//        $this->seeInDatabase();
        // Assert one or more files were stored...
        Storage::disk('images')->assertExists('photo1.jpg');
        Storage::disk('images')->assertExists(['photo1.jpg', 'photo2.jpg']);

        // Assert one or more files were not stored...
        Storage::disk('photos')->assertMissing('missing.jpg');
        Storage::disk('photos')->assertMissing(['missing.jpg', 'non-existing.jpg']);
    }

    /**
     * An image save from base64 test
     * @return void
     */
    public function storeBase64Test() {

    }

    /**
     * An image download from url test
     * @return void
     */
    public function imagesFromUrlTest() {

    }

    /**
     * An image show test
     * @return void
     */
    public function showTest() {

    }

    /**
     * An image deleting test
     * @return void
     */
    public function destroyTest() {

    }
}
