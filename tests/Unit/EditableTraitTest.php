<?php

namespace Dotlogics\Grapesjs\Tests\Unit;

use Dotlogics\Grapesjs\App\Traits\EditableTrait;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class EditableTraitTest extends TestCase
{
    public function test_get_editor_page_title_attribute()
    {
        $model = new class extends Model {
            use EditableTrait;

            protected $fillable = ['name', 'title', 'slug'];
        };

        $model->name = 'Test Page';
        $this->assertEquals('Test Page Page', $model->getEditorPageTitleAttribute());

        $model->name = null;
        $model->title = 'Test Title';
        $this->assertEquals('Test Title Model', $model->getEditorPageTitleAttribute());
    }

    public function test_gjs_data_getter_setter()
    {
        $model = new class extends Model {
            use EditableTrait;
            protected $attributes = [];
        };

        $data = ['html' => '<p>test</p>', 'css' => 'p { color: red; }'];
        $model->gjs_data = $data;

        $this->assertEquals($data, $model->getGjsDataAttribute($model->attributes['gjs_data']));
    }

    public function test_html_getter_processes_placeholders()
    {
        $model = new class extends Model {
            use EditableTrait;
            protected $attributes = ['gjs_data' => '{"html":"<p>[[test-placeholder]]</p>"}'];
        };

        $model->setPlaceholder('[[test-placeholder]]', '<strong>replaced</strong>');

        $this->assertEquals('<p><strong>replaced</strong></p>', $model->html);
    }
}