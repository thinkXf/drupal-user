<?php

namespace Drupal\Tests\graphql\Kernel\DataProducer\Entity\Fields\Image;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Tests\graphql\Kernel\GraphQLTestBase;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;

/**
 * Test class for the ImageDerivative data producer.
 *
 * @group graphql
 */
class ImageDerivativeTest extends GraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['image', 'file'];

  /**
   * The file system URI under test.
   *
   * @var string
   */
  protected $fileUri;

  /**
   * The file entity mock.
   *
   * @var \Drupal\file\FileInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $file;

  /**
   * The image style for testing.
   *
   * @var \Drupal\image\Entity\ImageStyle
   */
  protected $style;

  /**
   * A file entity mock that returns FALSE on access checking.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $fileNotAccessible;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->fileUri = 'public://test.jpg';

    $this->file = $this->getMockBuilder(FileInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->file->method('getFileUri')->willReturn($this->fileUri);
    $this->file->method('access')->willReturn((new AccessResultAllowed())->addCacheTags(['test_tag']));

    $this->style = ImageStyle::create(['name' => 'test_style']);
    $effect = [
      'id' => 'image_resize',
      'data' => [
        'width' => 300,
        'height' => 200,
      ],
    ];

    $this->style->addImageEffect($effect);
    $this->style->save();

    $this->fileNotAccessible = $this->getMockBuilder(FileInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->fileNotAccessible->method('access')->willReturn((new AccessResultForbidden())->addCacheTags(['test_tag_forbidden']));
  }

  /**
   * @covers \Drupal\graphql\Plugin\GraphQL\DataProducer\Entity\Fields\Image\ImageDerivative::resolve
   */
  public function testImageDerivative(): void {
    // Test that we get the proper style and dimensions if we have access to the
    // file.
    $result = $this->executeDataProducer('image_derivative', [
      'entity' => $this->file,
      'style' => 'test_style',
    ]);

    $this->assertEquals(
      [
        'url' => $this->style->buildUrl($this->fileUri),
        'width' => 300,
        'height' => 200,
      ],
      $result
    );

    $this->assertContains('config:image.style.test_style', $this->fieldContext->getCacheTags());
    $this->assertContains('test_tag', $this->fieldContext->getCacheTags());

    // Test that we don't get the derivative if we don't have access to the
    // original file, but we still get the access result cache tags.
    $result = $this->executeDataProducer('image_derivative', [
      'entity' => $this->fileNotAccessible,
      'style' => 'test_style',
    ]);

    $this->assertNull($result);
    $this->assertContains('test_tag_forbidden', $this->fieldContext->getCacheTags());
  }

  /**
   * Test that a SVG file is not processed.
   */
  public function testSvg(): void {
    $this->file->method('getMimeType')->willReturn('image/svg+xml');
    $result = $this->executeDataProducer('image_derivative', [
      'entity' => $this->file,
      'style' => 'test_style',
    ]);
    $this->assertNull($result);
  }

}
