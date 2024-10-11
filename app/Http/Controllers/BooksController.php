<?php

namespace App\Http\Controllers;

use App\Book;
use App\Exports\QuestionExport;
use App\Rules\CheckBook;
use App\Rules\CheckSubjectSingleQuote;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class BooksController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:Admin']);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['page_title'] = 'Book List';
        $data['result']     = Book::where('status', 1)->orderBy('id', 'DESC')->get();
        return view('book.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data['page_title'] = 'Add Book';
        return view('book.add', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $this->validate($request, [
            'subject_id' => 'required',
            'volumn'     => 'required',
            'author'     => 'required',
            'type_id'    => 'required',
            'book_name'  => ['required',new CheckSubjectSingleQuote(),new CheckBook($request->all())],
        ]);
        $book             = new Book();
        $book->subject_id = $request->input('subject_id');
        $book->volumn     = $request->input('volumn');
        $book->author     = strtolower(trim($request->input('author')));
        $book->level      = $request->input('type_id');
        $book->book_name  = strtolower(trim($request->input('book_name')));
        $book->created_by = session('user_info')['admin_id'];
        $book->updated_by = 0;
        $book->save();
        return redirect('admin/book')->with('msg_success', 'Book created successfully.');
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $id                 = (int)base64url_decode($id);
        $data['page_title'] = 'Edit Book';
        $row                = Book::findOrFail($id);
        if ($row) {
            $data['row'] = $row;
            return view('book.edit', $data);
        } else {
            return back()->with('msg_fail', 'No Record found against Id.');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $id   = base64url_decode($id);
        $book = Book::findOrFail($id);
        $this->validate($request, [
            'subject_id' => 'required',
            'volumn'     => 'required',
            'author'     => 'required',
            'type_id'    => 'required',
            'book_name'  => ['required',new CheckSubjectSingleQuote(),new CheckBook($request->all())],
        ]);

        $book->subject_id = $request->input('subject_id');
        $book->volumn     = $request->input('volumn');
        $book->author     = strtolower(trim($request->input('author')));
        $book->level      = $request->input('type_id');
        $book->book_name  = strtolower(trim($request->input('book_name')));
        $book->updated_by = session('user_info')['admin_id'];;
        $book->save();
        return redirect('admin/book')->with('msg_success', 'Book update successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $id   = base64url_decode($id);
        $book = Book::findOrFail($id);
        $book->status = 0;
        $book->save();
        return redirect('admin/book')->with('msg_success', 'Book deleted successfully');
    }



    public function buildingPlansExportXls()
    {

      return Excel::download(new QuestionExport(), 'Question_Template' . strtotime(date('Y-m-d H:i:s')) . '.xlsx');
    }
}
