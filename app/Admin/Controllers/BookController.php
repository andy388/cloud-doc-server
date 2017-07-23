<?php
/**
 * Created by PhpStorm.
 * User: ZhangWei
 * Date: 2017/7/23
 * Time: 16:26
 */

namespace App\Admin\Controllers;


use App\Http\Controllers\Controller;
use App\Models\Doc;
use App\Models\DocPage;
use Illuminate\Http\Request;

class BookController extends Controller
{

    public function edit(Request $request)
    {
        $doc_id = $request->input("doc_id");

        $doc = Doc::query()->find($doc_id);

        $data['doc_id'] = $doc_id;
        $data['doc'] = $doc;

        return view('admin.view.book', $data);
    }


    public function get_tree(Request $request)
    {
        $doc_id = $request->input("doc_id");

        $page = DocPage::query()->where("doc_id", $doc_id)
            ->where("parent_id", 0)
            ->orderBy("order", "desc")
            //->orderBy("id", "asc")
            ->select([
                'id',
                'title',
                'order',
                'parent_id'
            ])->get();


        return $page;
    }

    public function set_order(Request $request)
    {
        $t_id = $request->input("t_id");
        $s_id = $request->input("s_id");
        $point = $request->input("point");
        $t_page = DocPage::query()->find($t_id);
        $s_page = DocPage::query()->find($s_id);
        $state = false;
        switch ($point) {
            case 'top':
                $s_page->order = $t_page->order + 1;
                $s_page->parent_id = $t_page->parent_id;

                DocPage::query()->where("doc_id", $t_page->doc_id)
                    ->where("parent_id", $t_page->parent_id)
                    ->where("order", "<=", $t_page->order)->decrement("order");


                $state = $s_page->save();
                break;
            case 'bottom':
                $s_page->order = $t_page->order - 1;
                $s_page->parent_id = $t_page->parent_id;
                DocPage::query()->where("doc_id", $t_page->doc_id)
                    ->where("parent_id", $t_page->parent_id)
                    ->where("order", "<", $t_page->order)->decrement("order");
                $state = $s_page->save();
                break;
            case 'append':
                $s_page->order = 99999;
                $s_page->parent_id = $t_page->id;
                $state = $s_page->save();
                break;
        }
        return ['state' => $state];
    }

    public function add_page(Request $request)
    {
        $doc_id = $request->input("doc_id");
        $parent_id = $request->input("parent_id");
        $title = $request->input("title");


        $s_order = DocPage::query()->where("doc_id", $doc_id)
            ->where("parent_id", $parent_id)->orderBy("order", "asc")->first(['id', 'order']);
        if (empty($s_order)) {
            $order = 99999;
        } elseif ($s_order->order <= 1) {
            $order = 1;
        } else {
            $order = $s_order->order - 1;
        }

        $data = [
            'title' => $title,
            'parent_id' => $parent_id,
            'menu_title' => $title,
            'content' => "#" . $title,
            'order' => $order,
            'state' => 1,
            'doc_id' => $doc_id,
            'menu_id' => 0
        ];

        $page = DocPage::query()->create($data);

        return ['page' => $page, 's_page' => $s_order];
    }

    public function edit_title(Request $request)
    {
        $id = $request->input("id");
        $title = $request->input("title");

        $page = DocPage::query()->find($id);

        $page->title = $title;

        $state = $page->save();

        return ['state' => $state];
    }

    public function edit_content(Request $request){
        $id = $request->input("id");
        $page = DocPage::query()->find($id);

        return view("admin.view.book-edit-content",$page);
    }
}