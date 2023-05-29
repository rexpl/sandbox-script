<?php

declare(strict_types=1);

namespace Rexpl\SandBoxScript;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Rexpl\SandBoxScript\Compiler\Compiler;
use Rexpl\SandBoxScript\Contracts\Extension;
use Rexpl\SandBoxScript\Exceptions\RuntimeException;
use Rexpl\SandBoxScript\Extensions\DebugFunctions;
use Rexpl\SandBoxScript\Extensions\UserExtension;
use Rexpl\SandBoxScript\TwigReturn\ReturnToken;
use Rexpl\SandBoxScript\Extensions\ArrayFunctions;
use Rexpl\SandBoxScript\Extensions\StringFunctions;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\SandboxExtension;
use Twig\Loader\FilesystemLoader;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityPolicy;

class Runtime
{
    /**
     * The return value from the run function.
     *
     * @var array<mixed|null>
     */
    protected static array $returnValues = [];


    /**
     * The run id to match the return value.
     *
     * @var int
     */
    protected static int $nextRunId = 0;


    /**
     * Is the environment booted, extension loaded ?
     *
     * @var bool
     */
    protected bool $isBooted = false;


    /**
     * The twig environment.
     *
     * @var \Twig\Environment
     */
    protected Environment $twig;


    /**
     * The registered extensions.
     *
     * @var array<\Rexpl\SandBoxScript\Contracts\Extension>
     */
    protected array $extensions = [];


    /**
     * @var array<string,string>
     */
    protected array $builtInExtensions = [
        'str' => StringFunctions::class,
        'array' => ArrayFunctions::class,
        'debug' => DebugFunctions::class,
    ];


    /**
     * The context of the twig environment.
     *
     * @var array
     */
    protected array $context = [];


    /**
     * @var \Rexpl\SandBoxScript\Compiler\Compiler
     */
    protected Compiler $compiler;


    /**
     * @var array<string>
     */
    protected array $compiledFunctions = [];


    /**
     * @var \League\Flysystem\Filesystem
     */
    protected Filesystem $filesystem;


    /**
     * @param string $cacheDirectory
     */
    public function __construct(protected string $cacheDirectory)
    {
        $this->instantiateFilesystem();
        $this->instantiateTwig();
        $this->fetchCompiledFunctions();
    }


    /**
     * Instantiate the filesystem instance, and makes sure the working directory exists.
     *
     * @return void
     */
    protected function instantiateFilesystem(): void
    {
        $adapter = new LocalFilesystemAdapter($this->cacheDirectory);
        $this->filesystem = new Filesystem($adapter);

        $this->filesystem->createDirectory('/sbs');
    }


    /**
     * Instantiate the twig env and add the return tag.
     *
     * @return void
     */
    protected function instantiateTwig(): void
    {
        $loader = new FilesystemLoader($this->cacheDirectory . '/sbs');
        $this->twig = new Environment($loader, [
            'cache' => $this->cacheDirectory . '/twig',
            'strict_variables' => true,
        ]);
        $this->twig->addTokenParser(new ReturnToken());
    }


    /**
     * Fetches the compiled functions hashes avoiding a round trip to the filesystem for each execution.
     *
     * @return void
     */
    protected function fetchCompiledFunctions(): void
    {
        $allCompiledFunctions = $this->filesystem->listContents('/sbs');

        foreach ($allCompiledFunctions->toArray() as $file) {

            // We save it as being compiled.
            $this->compiledFunctions[] = substr($file->path(), 3);
        }
    }


    /**
     * Loads all the extensions and allow their methods in the policy + boots the compiler.
     * We are booting the compiler now because it needs to be aware of the know extensions.
     *
     * @return void
     */
    protected function bootTwig(): void
    {
        // We allow all the methods of the built-in extensions, and instantiate them.
        foreach ($this->builtInExtensions as $namespace => $extension) {

            $builtInExtensions[$namespace] = new $extension;
            $allowedMethods[$extension] = get_class_methods($extension);
        }

        // We add the sandbox mode for the environment.
        $policy = new SecurityPolicy(
            ['if', 'set', 'return', 'for', 'do'],
            allowedMethods: $allowedMethods,
            allowedProperties: [UserExtension::class => array_keys($this->extensions)],
        );
        $this->twig->addExtension(new SandboxExtension($policy, true));

        // We create the context with all the methods.
        $builtInExtensions['ext'] = new UserExtension($this->extensions, $policy, new AllowedMethods($allowedMethods));
        $this->context = $builtInExtensions;

        $this->compiler = new Compiler(array_keys($builtInExtensions));

        $this->isBooted = true;
    }


    /**
     * Register an extension in the given runtime.
     * Note: All extensions added after the first run won't be registered.
     *
     * @param \Rexpl\SandBoxScript\Contracts\Extension $extension
     *
     * @return $this
     */
    public function addExtension(Extension $extension): static
    {
        $this->extensions[$extension->namespace()] = $extension;

        return $this;
    }


    /**
     * Runs a given function and returns the result.
     *
     * @param string $function
     * @param array $input
     *
     * @return \Rexpl\SandBoxScript\ReturnResult
     *
     * @throws \Rexpl\SandBoxScript\Exceptions\RuntimeException
     * @throws \Rexpl\SandBoxScript\Exceptions\CompileException
     */
    public function run(string $function, array $input = []): ReturnResult
    {
        if (!$this->isBooted) $this->bootTwig();

        $runId = $this->getRunId();
        $functionHash = md5($function);

        if (!$this->hasCompiledFunction($functionHash)) $this->compile($functionHash, $function);

        $context = $this->context;
        $context['input'] = $input;
        $context['runId'] = $runId;

        try {

            $output = $this->twig->render($functionHash, $context);

        } catch (RuntimeError|SyntaxError|SecurityError $e) {

            throw new RuntimeException(
                sprintf(
                    '%s, line %s.', trim($e->getMessage(), '.'), $e->getLine()
                ),
                $e
            );
        }

        return new ReturnResult(
            $output,
            $this->getReturnValue($runId),
            $runId
        );
    }


    /**
     * @param string $hash
     *
     * @return bool
     */
    protected function hasCompiledFunction(string $hash): bool
    {
        return in_array($hash, $this->compiledFunctions);
    }


    /**
     * Compiles a given function to twig.
     *
     * @param string $hash
     * @param string $function
     *
     * @return void
     * @throws \Rexpl\SandBoxScript\Exceptions\CompileException
     */
    protected function compile(string $hash, string $function): void
    {
        $compiledFunction = $this->compiler->compile($function);

        $this->filesystem->write('sbs/' . $hash, $compiledFunction);
        $this->compiledFunctions[] = $hash;
    }


    /**
     * Returns a currently unique run id.
     *
     * @return int
     */
    protected function getRunId(): int
    {
        return ++self::$nextRunId;
    }


    /**
     * Returns the result by run id, we make sure to clear the result in case someone with the same run id
     * runs a function and doesn't return a result and to avoid memory leaks.
     *
     * @param int $runId
     *
     * @return mixed
     */
    protected function getReturnValue(int $runId): mixed
    {
        $value = self::$returnValues[$runId] ?? null;
        unset(self::$returnValues[$runId]);

        return $value;
    }


    /**
     * @internal
     *
     * @param string $runId
     * @param mixed $value
     *
     * @return void
     */
    public static function return(string $runId, mixed $value): void
    {
        self::$returnValues[$runId] = $value;
    }
}