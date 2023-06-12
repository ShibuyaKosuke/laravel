<?php

namespace App\Console\Commands;

use Hoa\File\File;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class ViewMakeCommand extends Command
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected Filesystem $files;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:view {model}
        {--i|index : Create a new index.blade.php for the model}
        {--s|show : Create a new show.blade.php for the model}
        {--c|create : Create a new create.blade.php for the model}
        {--e|edit : Create a new edit.blade.php for the model}
        {--C|crud : Create a new index, show, create and edit for the model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new view file';

    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('crud')) {
            $this->input->setOption('index', true);
            $this->input->setOption('show', true);
            $this->input->setOption('create', true);
            $this->input->setOption('edit', true);
        }

        if ($this->option('index')) {
            $this->createIndex();
        }

        if ($this->option('show')) {
            $this->createShow();
        }

        if ($this->option('create')) {
            $this->createCreate();
        }

        if ($this->option('edit')) {
            $this->createEdit();
        }
    }

    /**
     * @param string $type
     * @return string
     */
    public function getStub(string $type): string
    {
        return $this->resolveStubPath("/stubs/{$type}.blade.stub");
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param string $stub
     * @return string
     */
    protected function resolveStubPath(string $stub): string
    {
        return file_exists($customPath = app()->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__ . $stub;
    }

    private function createIndex()
    {
        $modelName = $this->qualifyClass($this->getNameInput());

        $stub = $this->files->get($this->getStub('index'));

        $stub = $this->replaceNamespace($stub, $modelName);

        $this->save($this->getTableName(), 'index.blade.php', $stub);
    }

    private function createShow()
    {
        $modelName = Str::studly(class_basename($this->argument('model')));

        $stub = $this->files->get($this->getStub('show'));

        $stub = $this->replaceNamespace($stub, $modelName);

        $this->save($this->getTableName(), 'show.blade.php', $stub);
    }

    /**
     * @param string $model
     * @return void
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    private function createCreate()
    {
        $modelName = Str::studly(class_basename($this->argument('model')));

        $stub = $this->files->get($this->getStub('create'));

        $stub = $this->replaceNamespace($stub, $modelName);

        $this->save($this->getTableName(), 'create.blade.php', $stub);
    }

    /**
     * @param string $model
     * @return void
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    private function createEdit()
    {
        $modelName = Str::studly(class_basename($this->argument('model')));

        $stub = $this->files->get($this->getStub('edit'));

        $stub = $this->replaceNamespace($stub, $modelName);

        $this->save($this->getTableName(), 'edit.blade.php', $stub);
    }

    private function save($dir, $name, $stub)
    {
        $this->files->ensureDirectoryExists(app()->viewPath($dir));
        $path = app()->viewPath($dir . '/' . $name);

        if ($this->files->exists($path)) {
            $this->error("View {$dir}/{$name} already exists!");
            return;
        }

        $this->files->put($path, $stub);

        $this->info("View {$dir}/{$name} created successfully.");
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput(): string
    {
        return trim($this->argument('model'));
    }

    /**
     * Parse the class name and format according to the root namespace.
     *
     * @param string $name
     * @return string
     */
    protected function qualifyClass(string $name): string
    {
        $name = ltrim($name, '\\/');

        $name = str_replace('/', '\\', $name);

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($name, $rootNamespace)) {
            return $name;
        }

        return $this->qualifyClass(
            $this->getDefaultNamespace(trim($rootNamespace, '\\')) . '\\' . $name
        );
    }

    /**
     * Get the root namespace for the class.
     *
     * @return string
     */
    protected function rootNamespace(): string
    {
        return $this->laravel->getNamespace();
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace(string $rootNamespace): string
    {
        return is_dir(app_path('Models')) ? $rootNamespace . '\\Models' : $rootNamespace;
    }

    /**
     * Replace the namespace for the given stub.
     *
     * @param string $stub
     * @param string $name
     * @return string
     */
    protected function replaceNamespace(string $stub, string $name)
    {
        $searches = [
            ['{{ tables }}', '{{ table }}'],
        ];

        foreach ($searches as $search) {
            $stub = str_replace(
                $search,
                [$this->getTableName(), $this->getModelName()],
                $stub
            );
        }

        return $stub;
    }

    /**
     * Get the full namespace for a given class, without the class name.
     *
     * @param string $name
     * @return string
     */
    protected function getNamespace(string $name): string
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }

    /**
     * @return string
     */
    private function getTableName()
    {
        $token = explode('/', class_basename($this->argument('model')));
        $basename = end($token);
        return Str::snake(Str::plural($basename));
    }

    /**
     * @return string
     */
    private function getModelName()
    {
        return Str::singular($this->getTableName());
    }
}
