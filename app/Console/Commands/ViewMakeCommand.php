<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command as CommandAlias;

/**
 * Class ViewMakeCommand
 */
class ViewMakeCommand extends Command
{
    /**
     * The filesystem instance.
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
    public function handle(): int
    {
        if ($this->option('crud')) {
            $this->input->setOption('index', true);
            $this->input->setOption('show', true);
            $this->input->setOption('create', true);
            $this->input->setOption('edit', true);
        }

        try {
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

            return CommandAlias::SUCCESS;

            // @codeCoverageIgnoreStart
        } catch (\Throwable $e) {
            return CommandAlias::FAILURE;
            // @codeCoverageIgnoreEnd
        }
    }

    public function getStub(string $type): string
    {
        return $this->resolveStubPath("/stubs/{$type}.blade.stub");
    }

    /**
     * Resolve the fully-qualified path to the stub.
     */
    protected function resolveStubPath(string $stub): string
    {
        return file_exists($customPath = app()->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    /**
     * @throws FileNotFoundException
     */
    private function createIndex(): void
    {
        $stub = $this->files->get($this->getStub('index'));

        $stub = $this->replaceNamespace($stub);

        $this->save($this->getTableName(), 'index.blade.php', $stub);
    }

    /**
     * @throws FileNotFoundException
     */
    private function createShow(): void
    {
        $stub = $this->files->get($this->getStub('show'));

        $stub = $this->replaceNamespace($stub);

        $this->save($this->getTableName(), 'show.blade.php', $stub);
    }

    /**
     * @param  string  $model
     *
     * @throws FileNotFoundException
     */
    private function createCreate(): void
    {
        $stub = $this->files->get($this->getStub('create'));

        $stub = $this->replaceNamespace($stub);

        $this->save($this->getTableName(), 'create.blade.php', $stub);
    }

    /**
     * @param  string  $model
     *
     * @throws FileNotFoundException
     */
    private function createEdit(): void
    {
        $stub = $this->files->get($this->getStub('edit'));

        $stub = $this->replaceNamespace($stub);

        $this->save($this->getTableName(), 'edit.blade.php', $stub);
    }

    private function save(string $dir, string $name, string $stub): void
    {
        $this->files->ensureDirectoryExists(app()->viewPath($dir));
        $path = app()->viewPath($dir.'/'.$name);

        if ($this->files->exists($path)) {
            // @codeCoverageIgnoreStart
            $this->error("View {$dir}/{$name} already exists!");

            return;
            // @codeCoverageIgnoreEnd
        }

        $this->files->put($path, $stub);

        $this->info("View {$dir}/{$name} created successfully.");
    }

//    /**
//     * Get the root namespace for the class.
//     */
//    protected function rootNamespace(): string
//    {
//        return $this->laravel->getNamespace();
//    }
//
//    /**
//     * Get the default namespace for the class.
//     */
//    protected function getDefaultNamespace(string $rootNamespace): string
//    {
//        return is_dir(app_path('Models')) ? $rootNamespace.'\\Models' : $rootNamespace;
//    }

    /**
     * Replace the namespace for the given stub.
     */
    protected function replaceNamespace(string $stub): string
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
     */
//    protected function getNamespace(string $name): string
//    {
//        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
//    }

    private function getTableName(): string
    {
        $token = explode('/', class_basename($this->argument('model')));
        $basename = end($token);

        return Str::snake(Str::plural($basename));
    }

    private function getModelName(): string
    {
        return Str::singular($this->getTableName());
    }
}
