<?php

namespace App\Http\Controllers;

use App\Models\CronJob;
use App\Models\Site;
use App\Services\CommandService;
use Illuminate\Http\Request;

class CronJobController extends Controller
{
    public function __construct(private CommandService $cmd) {}

    public function store(Request $request, Site $site)
    {
        $validated = $request->validate([
            'cron_m'  => ['required', 'string', 'max:20', 'regex:/^[\d\*,\/\-]+$/'],
            'cron_h'  => ['required', 'string', 'max:20', 'regex:/^[\d\*,\/\-]+$/'],
            'cron_d'  => ['required', 'string', 'max:20', 'regex:/^[\d\*,\/\-]+$/'],
            'cron_mo' => ['required', 'string', 'max:20', 'regex:/^[\d\*,\/\-]+$/'],
            'cron_dw' => ['required', 'string', 'max:20', 'regex:/^[\d\*,\/\-]+$/'],
            'command' => ['required', 'string', 'max:500'],
            'run_as'  => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_\-]+$/'],
        ]);

        $schedule = "{$validated['cron_m']} {$validated['cron_h']} {$validated['cron_d']} {$validated['cron_mo']} {$validated['cron_dw']}";

        $job = CronJob::create([
            'site_id'  => $site->id,
            'schedule' => $schedule,
            'command'  => $validated['command'],
            'run_as'   => $validated['run_as'],
        ]);

        $this->writeCronFile($site);

        return response()->json(['message' => 'Cron job adicionado.', 'job' => $job]);
    }

    public function destroy(Site $site, CronJob $cronJob)
    {
        $cronJob->delete();
        $this->writeCronFile($site);
        return response()->json(['message' => 'Cron job removido.']);
    }

    private function writeCronFile(Site $site): void
    {
        $slug = 'gpanel-' . preg_replace('/[^a-zA-Z0-9]/', '-', $site->domain);
        $dest = '/etc/cron.d/' . $slug;
        $jobs = $site->cronJobs()->get();

        if ($jobs->isEmpty()) {
            $this->cmd->run('cron.remove', ['path' => $dest]);
            return;
        }

        $content  = "# GPanel cron jobs — {$site->domain}\n";
        $content .= "# Managed by GPanel — do not edit manually\n";
        $content .= "SHELL=/bin/bash\n";
        $content .= "PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin\n\n";
        foreach ($jobs as $job) {
            $content .= "{$job->schedule} {$job->run_as} {$job->command}\n";
        }

        $tmp = '/tmp/gpanel-cron-' . $site->id . '-' . time();
        file_put_contents($tmp, $content);
        $this->cmd->run('cron.install', ['src' => $tmp, 'dest' => $dest]);
    }
}
