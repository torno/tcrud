<?php

namespace Torno\TCrud\Examples;

use Torno\TCrud\Libraries\CrudBuilder;

class BooksController extends \CodeIgniter\Controller
{
    public function index()
    {
        $crud = new CrudBuilder();
        
        $crud->setTable('books')
             ->setSubject('Libro', 'Libros')
             ->columns([
                 'id', 'title', 'author_id', 'authors', 'category_id', 
                 'price', 'status', 'published_date'
             ])
             ->fields([
                 'title', 'isbn', 'author_id', 'authors', 'category_id',
                 'published_date', 'price', 'description', 'status'
             ])
             ->displayAs([
                 'title' => 'Título',
                 'isbn' => 'ISBN',
                 'author_id' => 'Autor Principal',
                 'authors' => 'Autores',
                 'category_id' => 'Categoría',
                 'published_date' => 'Fecha Publicación',
                 'price' => 'Precio',
                 'description' => 'Sinopsis',
                 'status' => 'Estado'
             ])
             ->setRelation('author_id', 'authors', 'name')
             ->setRelation('category_id', 'categories', 'name', 'name ASC', ['is_active' => 1])
             ->setRelationNtoN(
                 'authors', 'book_authors', 'authors',
                 'book_id', 'author_id', 'name', 'sort_order'
             )
             ->fieldType('description', 'wysiwyg')
             ->fieldType('status', 'dropdown', [
                 'draft' => 'Borrador',
                 'published' => 'Publicado',
                 'archived' => 'Archivado'
             ])
             ->fieldType('published_date', 'date')
             ->fieldType('price', 'number')
             ->fieldRequired(['title', 'author_id', 'category_id', 'price'])
             ->setValidationRules([
                 'title' => 'required|min_length[3]|max_length[255]',
                 'author_id' => 'required|integer|is_not_unique[authors.id]',
                 'category_id' => 'required|integer|is_not_unique[categories.id]',
                 'price' => 'required|numeric|greater_than[0]',
                 'isbn' => 'permit_empty|min_length[10]|max_length[20]',
                 'status' => 'permit_empty|in_list[draft,published,archived]'
             ])
             ->setHidden('status', 'draft', 'add')
             ->enableBulkDelete()
             ->enableInlineEdit(['title', 'price', 'status'])
             ->clearOnClone(['isbn'])
             ->callbackBeforeInsert(function($data) {
                 $data['created_at'] = date('Y-m-d H:i:s');
                 return $data;
             })
             ->callbackBeforeUpdate(function($data, $id) {
                 $data['updated_at'] = date('Y-m-d H:i:s');
                 return $data;
             });

        $output = $crud->render();

        if (isset($output->is_json) && $output->is_json) {
            return $this->response->setContentType('application/json')->setBody($output->output);
        }
        
        if ($output instanceof \CodeIgniter\HTTP\RedirectResponse) {
            return $output;
        }

        return view('layouts/admin', [
            'title'     => 'Gestión de Libros',
            'content'   => $output->output,
            'css_files' => $output->css_files ?? [],
            'js_files'  => $output->js_files ?? []
        ]);
    }
}