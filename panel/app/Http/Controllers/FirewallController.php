<?php

namespace App\Http\Controllers;

use App\Services\CommandService;
use Illuminate\Http\Request;

class FirewallController extends Controller
{
    public function __construct(private CommandService $cmd) {}

    public function index()
    {
        $rules  = $this->getRules();
        $status = $this->getStatus();
        return view('firewall.index', compact('rules', 'status'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'port'     => ['required', 'integer', 'min:1', 'max:65535'],
            'protocol' => ['required', 'in:tcp,udp'],
            'action'   => ['required', 'in:allow,deny'],
            'from'     => ['nullable', 'string', 'max:50', 'regex:/^[0-9a-fA-F.:\/]+$/'],
        ]);

        try {
            $params = [
                'action'   => $validated['action'],
                'port'     => (string) $validated['port'],
                'protocol' => $validated['protocol'],
            ];

            $op = 'ufw.rule';
            if (!empty($validated['from'])) {
                $params['from'] = $validated['from'];
                $op = 'ufw.rule_from';
            }

            $this->cmd->runOrFail($op, $params);
        } catch (\Throwable $e) {
            return back()->withErrors(['port' => 'Erro UFW: ' . $e->getMessage()]);
        }

        return back()->with('success', "Regra {$validated['action']} porta {$validated['port']}/{$validated['protocol']} adicionada.");
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'port'     => ['required', 'integer', 'min:1', 'max:65535'],
            'protocol' => ['required', 'in:tcp,udp'],
            'action'   => ['required', 'in:allow,deny'],
        ]);

        try {
            $this->cmd->runOrFail('ufw.delete', [
                'action'   => $validated['action'],
                'port'     => (string) $validated['port'],
                'protocol' => $validated['protocol'],
            ]);
        } catch (\Throwable $e) {
            return back()->withErrors(['port' => 'Erro UFW: ' . $e->getMessage()]);
        }

        return back()->with('success', 'Regra removida.');
    }

    // ---------------------------------------------------------------------- //

    private function getRules(): array
    {
        $output = [];
        exec('ufw status numbered 2>/dev/null', $output);
        $rules = [];
        foreach ($output as $line) {
            if (preg_match('/^\[\s*(\d+)\]\s+(.+?)\s+(ALLOW|DENY|LIMIT)\s*(IN|OUT|FWD)?\s*(.*)/i', trim($line), $m)) {
                $rules[] = [
                    'num'    => trim($m[1]),
                    'to'     => trim($m[2]),
                    'action' => strtolower(trim($m[3])),
                    'from'   => trim($m[5]) ?: 'Anywhere',
                ];
            }
        }
        return $rules;
    }

    private function getStatus(): string
    {
        $output = [];
        exec('ufw status 2>/dev/null', $output);
        return str_contains(strtolower(implode('', $output)), 'active') ? 'active' : 'inactive';
    }
}
