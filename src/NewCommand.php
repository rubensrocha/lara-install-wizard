<?php

namespace Rubensrocha\LaraWizard\Console;

use JsonException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class NewCommand extends Command
{
    /**
     * Additional packages
     * @var array
     */
    protected $additional_packages;

    /**
     * Laravel version
     * @var array
     */
    protected $laravel_version;

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure() :void
    {
        $this
            ->setName('new')
            ->setDescription('Create a new Laravel application')
            ->addArgument('name', InputArgument::OPTIONAL)
            ->addArgument('version', InputArgument::OPTIONAL)
            ->addOption('dev', null, InputOption::VALUE_NONE, 'Installs the latest "development" release')
            ->addOption('jet', null, InputOption::VALUE_NONE, 'Installs the Laravel Jetstream scaffolding')
            ->addOption('stack', null, InputOption::VALUE_OPTIONAL, 'The Jetstream stack that should be installed')
            ->addOption('teams', null, InputOption::VALUE_NONE, 'Indicates whether Jetstream should be scaffolded with team support')
            ->addOption('auth', null, InputOption::VALUE_NONE, 'Installs the Laravel authentication scaffolding')
            ->addOption('preset', null, InputOption::VALUE_OPTIONAL, 'The Laravel/UI preset that should be installed')
            ->addOption('telescope', null, InputOption::VALUE_NONE, 'Installs the Laravel Telescope(dev)')
            ->addOption('socialite', null, InputOption::VALUE_NONE, 'Installs the Laravel Socialite')
            ->addOption('passport', null, InputOption::VALUE_NONE, 'Installs the Laravel Passport')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Forces install even if the directory already exists');
    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws JsonException
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->setLaravelVersion($input->getArgument('version'));

        if ($input->getOption('jet') && $input->getOption('auth')) {
            throw new RuntimeException('It is not possible to install Jetstream and Laravel/UI at the same time!');
        }

        if($input->getOption('auth')){
            $this->checkAuthCompatibility();
            $output->write(PHP_EOL."<fg=yellow>
|                              |        .   .|
|    ,---.,---.,---..    ,,---.|        |   ||
|    ,---||    ,---| \  / |---'|        |   ||
`---'`---^`    `---^  `'  `---'`---'    `---'`
</>".PHP_EOL.PHP_EOL);
            $preset = $this->authPreset($input, $output);
        }

        if ($input->getOption('jet')) {
            $this->checkJetstreamCompatibility();
            $output->write(PHP_EOL."<fg=magenta>
    |     |         |
    |,---.|--- ,---.|--- ,---.,---.,---.,-.-.
    ||---'|    `---.|    |    |---',---|| | |
`---'`---'`---'`---'`---'`    `---'`---^` ' '</>".PHP_EOL.PHP_EOL);

            $stack = $this->jetstreamStack($input, $output);

            $teams = $input->getOption('teams') === true
                ? (bool) $input->getOption('teams')
                : (new SymfonyStyle($input, $output))->confirm('Will your application use teams?', false);
        }

        if ($input->getOption('telescope')) {
            $this->checkTelescopeCompatibility();
            $output->write(PHP_EOL . "<fg=cyan>
|                              |        --.--     |
|    ,---.,---.,---..    ,,---.|          |  ,---.|    ,---.,---.,---.,---.,---.,---.
|    ,---||    ,---| \  / |---'|          |  |---'|    |---'`---.|    |   ||   ||---'
`---'`---^`    `---^  `'  `---'`---'      `  `---'`---'`---'`---'`---'`---'|---'`---'
                                                                           |         </>" . PHP_EOL . PHP_EOL);
        }

        if ($input->getOption('socialite')) {
            $this->checkSocialiteCompatibility();
            $output->write(PHP_EOL . "<fg=yellow>
|                              |        ,---.          o     |    o|
|    ,---.,---.,---..    ,,---.|        `---.,---.,---..,---.|    .|--- ,---.
|    ,---||    ,---| \  / |---'|            ||   ||    |,---||    ||    |---'
`---'`---^`    `---^  `'  `---'`---'    `---'`---'`---'``---^`---'``---'`---'
</>" . PHP_EOL . PHP_EOL);
        }

        if ($input->getOption('passport')) {
            $this->checkPassportCompatibility();
            $output->write(PHP_EOL . "<fg=blue>
|                              |        ,---.                              |
|    ,---.,---.,---..    ,,---.|        |---',---.,---.,---.,---.,---.,---.|---
|    ,---||    ,---| \  / |---'|        |    ,---|`---.`---.|   ||   ||    |
`---'`---^`    `---^  `'  `---'`---'    `    `---^`---'`---'|---'`---'`    `---'
                                                            |                   </>" . PHP_EOL . PHP_EOL);
        } else {
            $output->write(PHP_EOL.'<fg=red> _                               _
| |                             | |
| |     __ _ _ __ __ ___   _____| |
| |    / _` | \'__/ _` \ \ / / _ \ |
| |___| (_| | | | (_| |\ V /  __/ |
|______\__,_|_|  \__,_| \_/ \___|_|</>'.PHP_EOL.PHP_EOL);
        }

        sleep(1);

        $output->writeln('<fg=blue>Starting Install Wizard...</>');

        $name = $input->getArgument('name');

        $output->writeln('<fg=blue>Project Name: </>'.$name);

        $directory = $name && $name !== '.' ? getcwd().'/'.$name : '.';

        if ($this->getVersion($input)) {
            $version = 'dev-develop';
        } else {
            $version = $this->laravel_version['major'] . '.' . $this->laravel_version['minor'] . '.' . $this->laravel_version['patch'];
        }

        $output->writeln('<fg=blue>Laravel Version: </>'.$version);

        if (! $input->getOption('force')) {
            $output->writeln('<fg=blue>Checking if the project already exists...</>');
            $this->verifyApplicationDoesntExist($directory);
        }

        if ($input->getOption('force') && $directory === '.') {
            throw new RuntimeException('Cannot use --force option when using current directory for installation!');
        }

        $output->writeln('<fg=blue>Searching composer.phar file...</>');

        $composer = $this->findComposer();

        $commands = [
            $composer." create-project laravel/laravel \"$directory\" $version --remove-vcs --prefer-dist",
        ];

        if ($directory !== '.' && $input->getOption('force')) {
            $output->writeln('<fg=blue>Deleting existing directory...</>');
            if (PHP_OS_FAMILY === 'Windows') {
                array_unshift($commands, "rd /s /q \"$directory\"");
            } else {
                array_unshift($commands, "rm -rf \"$directory\"");
            }
            $output->writeln('<fg=blue>Existing directory deleted successfully</>');
        }

        if (PHP_OS_FAMILY !== 'Windows') {
            $output->writeln('<fg=blue>Configuring folder permissions</>');
            $commands[] = "chmod 755 \"$directory/artisan\"";
        }

        $output->writeln('<fg=blue>Starting Laravel installation...</>');

        if (($process = $this->runCommands($commands, $input, $output))->isSuccessful()) {
            if ($name && $name !== '.') {
                $output->writeln('<fg=blue>Setting project name...</>');

                $this->replaceInFile(
                    'APP_URL=http://localhost',
                    'APP_URL=http://'.$name.'.test',
                    $directory.'/.env'
                );

                $this->replaceInFile(
                    'DB_DATABASE=laravel',
                    'DB_DATABASE='.str_replace('-', '_', strtolower($name)),
                    $directory.'/.env'
                );
            }

            if ($input->getOption('jet')) {
                $output->writeln('<fg=blue>Starting Jetstream installation...</>');
                $this->installJetstream($directory, $stack, $teams, $input, $output);
            }

            if ($input->getOption('auth')) {
                $output->writeln('<fg=blue>Starting Laravel/UI installation...</>');
                $this->installAuth($directory, $preset, $input, $output);
            }

            if ($input->getOption('telescope')) {
                $output->writeln('<fg=blue>Starting Telescope installation...</>');
                $this->installTelescope($directory, $input, $output);
            }

            if ($input->getOption('socialite')) {
                $output->writeln('<fg=blue>Starting Socialite installation...</>');
                $this->installSocialite($directory, $input, $output);
            }

            if ($input->getOption('passport')) {
                $output->writeln('<fg=blue>Starting Passport installation...</>');
                $this->installPassport($directory, $input, $output);
            }

            $output->writeln(PHP_EOL.'<comment>Application ready! Build something amazing.</comment>');
        }

        return $process->getExitCode();
    }

    /**
     * Set Laravel version or get latest release from Github
     *
     * @param string|null $lara_version
     * @throws JsonException
     * @return void
     */
    protected function setLaravelVersion(string $lara_version = null): void
    {
        if (!$lara_version) {
            $lara_version = $this->getLatestRelease();
        }

        $version = explode('.', $lara_version);
        $major = $version[0];
        $minor = $version[1] ?? '*';
        $patch = $version[2] ?? '*';

        $this->laravel_version = ['major' => $major, 'minor' => $minor, 'patch' => $patch];
    }

    /**
     * Get latest release version of Laravel
     *
     * @return string Latest version release
     * @throws JsonException
     */
    protected function getLatestRelease(): string
    {
        $api_url = 'https://api.github.com/repos/laravel/laravel/releases';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/vnd.github.v3+json',
            'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:28.0) Gecko/20100101 Firefox/28.0'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $data = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
        curl_close($ch);
        $version = reset($data)['tag_name'];
        return str_replace('v', '', $version);
    }

    /**
     * Check compatibility between Laravel version and Laravel/UI
     *
     * @return void
     */
    protected function checkAuthCompatibility(): void
    {
        if ($this->laravel_version['major'] && ($this->laravel_version['major'] <= '5' && ($this->laravel_version['minor'] !== '*' && $this->laravel_version['minor'] < '8'))) {
            throw new RuntimeException('It is not possible to install Laravel/UI on Laravel 5.7 or lower!');
        }

        if ($this->laravel_version['major'] <= '6') {
            $this->additional_packages['auth_version'] = '^1';
        }

        if ($this->laravel_version['major'] === '7') {
            $this->additional_packages['auth_version'] = '^2';
        }

        if ($this->laravel_version['major'] >= '8') {
            $this->additional_packages['auth_version'] = '^3';
        }
    }

    /**
     * Check compatibility between Laravel version and Jetstream
     *
     * @return void
     */
    protected function checkJetstreamCompatibility(): void
    {
        if ($this->laravel_version['major'] <= '7') {
            throw new RuntimeException('It is not possible to install Jetstream on Laravel 7 or lower!');
        }
    }

    /**
     * Check compatibility between Laravel version and Laravel/telescope
     *
     * @return void
     */
    protected function checkTelescopeCompatibility(): void
    {
        if ($this->laravel_version['major'] && ($this->laravel_version['major'] <= '5' && ($this->laravel_version['minor'] !== '*' && $this->laravel_version['minor'] <= '7' && ($this->laravel_version['patch'] !== '*' && $this->laravel_version['patch'] <= '6')))) {
            throw new RuntimeException('It is not possible to install Laravel/telescope on Laravel 5.7.6 or lower!');
        }

        if ($this->laravel_version['major'] === '5' && $this->laravel_version['minor'] !== '*' && $this->laravel_version['minor'] <= '7') {
            $this->additional_packages['telescope_version'] = '^1';
        }

        if ($this->laravel_version['major'] === '5' && ($this->laravel_version['minor'] === '*' || $this->laravel_version['minor'] >= '8')) {
            $this->additional_packages['telescope_version'] = '^2';
        }

        if ($this->laravel_version['major'] === '6' || $this->laravel_version['major'] === '7') {
            $this->additional_packages['telescope_version'] = '^3';
        }

        if ($this->laravel_version['major'] >= '8') {
            $this->additional_packages['telescope_version'] = '^4';
        }
    }

    /**
     * Check compatibility between Laravel version and Laravel/passport
     *
     * @return void
     */
    protected function checkPassportCompatibility(): void
    {
        if ($this->laravel_version['major'] && ($this->laravel_version['major'] <= '5' && ($this->laravel_version['minor'] !== '*' && $this->laravel_version['minor'] <= '2'))) {
            throw new RuntimeException('It is not possible to install Laravel/passport on Laravel 5.2 or lower!');
        }

        if ($this->laravel_version['major'] === '5') {
            if($this->laravel_version['minor'] !== '*' && $this->laravel_version['minor'] === '3'){
                $this->additional_packages['passport_version'] = '^1';
            }
            if($this->laravel_version['minor'] !== '*' && $this->laravel_version['minor'] === '4'){
                $this->additional_packages['passport_version'] = '^4';
            }
            if($this->laravel_version['minor'] === '*' || $this->laravel_version['minor'] >= '6'){
                $this->additional_packages['passport_version'] = '^7';
            }
        }

        if ($this->laravel_version['major'] === '6') {
            if($this->laravel_version['minor'] !== '*' && $this->laravel_version['minor'] < '18'){
                $this->additional_packages['passport_version'] = '^8';
            }else{
                $this->additional_packages['passport_version'] = '^9';
            }
        }

        if ($this->laravel_version['major'] === '7') {
            if($this->laravel_version['minor'] !== '*' && $this->laravel_version['minor'] < '22'){
                $this->additional_packages['passport_version'] = '^8';
            }else{
                $this->additional_packages['passport_version'] = '^9';
            }
        }

        if (($this->laravel_version['major'] >= '8') && $this->laravel_version['minor'] !== '*' && $this->laravel_version['minor'] >= '2') {
            $this->additional_packages['passport_version'] = '^10';
        }
    }

    /**
     * Check compatibility between Laravel version and Laravel/socialite
     *
     * @return void
     */
    protected function checkSocialiteCompatibility(): void
    {
        if ($this->laravel_version['major'] && ($this->laravel_version['major'] <= '4' && ($this->laravel_version['minor'] !== '*' && $this->laravel_version['minor'] <= '2'))) {
            throw new RuntimeException('It is not possible to install Laravel/socialite on Laravel 4.2 or lower!');
        }

        if (($this->laravel_version['major'] === '4') && $this->laravel_version['minor'] !== '*' && $this->laravel_version['minor'] === '3') {
            $this->additional_packages['socialite_version'] = '^1';
        }

        if ($this->laravel_version['major'] === '5') {
            if($this->laravel_version['minor'] !== '*' && $this->laravel_version['minor'] < '4'){
                $this->additional_packages['socialite_version'] = '^2';
            }
            if($this->laravel_version['minor'] !== '*' && $this->laravel_version['minor'] < '7'){
                $this->additional_packages['socialite_version'] = '^3';
            }else{
                $this->additional_packages['socialite_version'] = '^4';
            }
        }

        if ($this->laravel_version['major'] >= '6') {
            $this->additional_packages['socialite_version'] = '^5';
        }
    }

    /**
     * Install Laravel UI into the application.
     *
     * @param string $directory
     * @param string $preset
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function installAuth(string $directory, string $preset, InputInterface $input, OutputInterface $output): void
    {
        chdir($directory);

        $output->writeln('<fg=blue>Laravel/UI Version: </>');
        $ui_command = $this->additional_packages['auth_version'] ? ' require laravel/ui "' . $this->additional_packages['auth_version'] . '"' : ' require laravel/ui';

        $commands = array_filter([
            $this->findComposer() . $ui_command,
            PHP_BINARY . ' artisan ui ' . $preset . ' --auth',
            'npm install && npm run dev',
        ]);

        $this->runCommands($commands, $input, $output);
    }

    /**
     * Determine the preset for Laravel/UI.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string
     */
    protected function authPreset(InputInterface $input, OutputInterface $output): string
    {
        $presets = [
            'bootstrap',
            'vue',
            'react',
        ];

        if ($input->getOption('preset') && in_array($input->getOption('preset'), $presets, false)) {
            return $input->getOption('preset');
        }

        $helper = $this->getHelper('question');

        $question = new ChoiceQuestion('Which UI preset do you prefer?', $presets);

        $output->write(PHP_EOL);

        return $helper->ask($input, new SymfonyStyle($input, $output), $question);
    }

    /**
     * Install Laravel Jetstream into the application.
     *
     * @param  string  $directory
     * @param  string  $stack
     * @param  bool  $teams
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function installJetstream(string $directory, string $stack, bool $teams, InputInterface $input, OutputInterface $output): void
    {
        chdir($directory);

        $output->writeln('<fg=blue>Jetstream Stack: </>'.$stack);
        $output->writeln('<fg=blue>Jetstream Teams: </>'.$teams ? 'Yes': 'No');

        $commands = array_filter([
            $this->findComposer().' require laravel/jetstream',
            trim(sprintf(PHP_BINARY.' artisan jetstream:install %s %s', $stack, $teams ? '--teams' : '')),
            $stack === 'inertia' ? 'npm install && npm run dev' : null,
            PHP_BINARY.' artisan storage:link',
        ]);

        $this->runCommands($commands, $input, $output);
    }

    /**
     * Determine the stack for Jetstream.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string
     */
    protected function jetstreamStack(InputInterface $input, OutputInterface $output): string
    {
        $stacks = [
            'livewire',
            'inertia',
        ];

        if ($input->getOption('stack') && in_array($input->getOption('stack'), $stacks, false)) {
            return $input->getOption('stack');
        }

        $helper = $this->getHelper('question');

        $question = new ChoiceQuestion('Which Jetstream stack do you prefer?', $stacks);

        $output->write(PHP_EOL);

        return $helper->ask($input, new SymfonyStyle($input, $output), $question);
    }

    /**
     * Install Laravel Telescope into the application.
     *
     * @param string $directory
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function installTelescope(string $directory, InputInterface $input, OutputInterface $output): void
    {
        chdir($directory);

        $output->writeln('<fg=blue>Telescope Version: </>'.$this->additional_packages['telescope_version']);

        $ui_command = $this->additional_packages['telescope_version'] ? ' require laravel/telescope "' . $this->additional_packages['telescope_version'] . '"' : ' require laravel/telescope';

        $commands = array_filter([
            $this->findComposer() . $ui_command . ' --dev',
            PHP_BINARY . ' artisan telescope:install',
        ]);

        $this->runCommands($commands, $input, $output);
    }

    /**
     * Install Laravel Socialite into the application.
     *
     * @param string $directory
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function installSocialite(string $directory, InputInterface $input, OutputInterface $output): void
    {
        chdir($directory);

        $output->writeln('<fg=blue>Socialite Version: </>'.$this->additional_packages['socialite_version']);

        $ui_command = $this->additional_packages['socialite_version'] ? ' require laravel/socialite "' . $this->additional_packages['socialite_version'] . '"' : ' require laravel/socialite';

        $commands = array_filter([
            $this->findComposer() . $ui_command
        ]);

        $this->runCommands($commands, $input, $output);
    }

    /**
     * Install Laravel Passport into the application.
     *
     * @param string $directory
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function installPassport(string $directory, InputInterface $input, OutputInterface $output): void
    {
        chdir($directory);

        $output->writeln('<fg=blue>Passport Version: </>'.$this->additional_packages['passport_version']);

        $ui_command = $this->additional_packages['passport_version'] ? ' require laravel/passport "' . $this->additional_packages['passport_version'] . '"' : ' require laravel/passport';

        $commands = array_filter([
            $this->findComposer() . $ui_command
        ]);

        $this->runCommands($commands, $input, $output);
    }

    /**
     * Verify that the application does not already exist.
     *
     * @param string $directory
     * @return void
     */
    protected function verifyApplicationDoesntExist(string $directory): void
    {
        if ((is_dir($directory) || is_file($directory)) && $directory != getcwd()) {
            throw new RuntimeException('Application already exists!');
        }
    }

    /**
     * Get the version that should be downloaded.
     *
     * @param InputInterface $input
     * @return boolean
     */
    protected function getVersion(InputInterface $input): bool
    {
        if ($input->getOption('dev')) {
            return true;
        }

        return false;
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposer(): string
    {
        $composerPath = getcwd().'/composer.phar';

        if (file_exists($composerPath)) {
            return '"'.PHP_BINARY.'" '.$composerPath;
        }

        return 'composer';
    }

    /**
     * Run the given commands.
     *
     * @param  array  $commands
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return Process
     */
    protected function runCommands(array $commands, InputInterface $input, OutputInterface $output): Process
    {
        if ($input->getOption('no-ansi')) {
            $commands = array_map(static function ($value) {
                if (substr($value, 0, 5) === 'chmod') {
                    return $value;
                }

                return $value.' --no-ansi';
            }, $commands);
        }

        if ($input->getOption('quiet')) {
            $commands = array_map(static function ($value) {
                if (substr($value, 0, 5) === 'chmod') {
                    return $value;
                }

                return $value.' --quiet';
            }, $commands);
        }

        $process = Process::fromShellCommandline(implode(' && ', $commands), null, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            try {
                $process->setTty(true);
            } catch (RuntimeException $e) {
                $output->writeln('Warning: '.$e->getMessage());
            }
        }

        $process->run(function ($type, $line) use ($output) {
            $output->write('    '.$line);
        });

        return $process;
    }

    /**
     * Replace the given string in the given file.
     *
     * @param  string  $search
     * @param  string  $replace
     * @param  string  $file
     * @return void
     */
    protected function replaceInFile(string $search, string $replace, string $file): void
    {
        file_put_contents(
            $file,
            str_replace($search, $replace, file_get_contents($file))
        );
    }
}
