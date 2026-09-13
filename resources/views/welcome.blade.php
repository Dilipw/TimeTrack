<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta
        name="description"
        content="TimeTrack — Project-based employee time tracking, timesheet approval and payroll management."
    >

    <title>{{ config('app.name', 'TimeTrack') }} — Employee Time & Payroll Management</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">

    {{-- Background --}}
    <div class="fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute left-1/2 top-[-300px] h-[600px] w-[900px] -translate-x-1/2 rounded-full bg-amber-500/10 blur-3xl"></div>
        <div class="absolute bottom-[-250px] right-[-150px] h-[500px] w-[500px] rounded-full bg-blue-500/10 blur-3xl"></div>
    </div>

    {{-- Navigation --}}
    <header class="border-b border-white/10 bg-slate-950/70 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5 lg:px-8">

            <a href="/" class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-400 text-slate-950 shadow-lg shadow-amber-400/20">
                    <svg
                        class="h-6 w-6"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 7v5l3 2"/>
                    </svg>
                </div>

                <div>
                    <div class="text-lg font-bold tracking-tight">
                        TimeTrack
                    </div>
                    <div class="text-[10px] font-medium uppercase tracking-[0.2em] text-slate-500">
                        Workforce Management
                    </div>
                </div>
            </a>

            <div class="flex items-center gap-3">
                @auth
                    <a
                        href="{{ url('/user') }}"
                        class="rounded-lg bg-amber-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-amber-300"
                    >
                        Open Dashboard
                    </a>
                @else
                    <a
                        href="{{ url('/user/login') }}"
                        class="rounded-lg border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/10"
                    >
                        Sign In
                    </a>
                @endauth
            </div>

        </div>
    </header>


    {{-- Hero --}}
    <main>

        <section class="relative overflow-hidden">
            <div class="mx-auto max-w-7xl px-6 pb-20 pt-20 lg:px-8 lg:pb-28 lg:pt-28">

                <div class="mx-auto max-w-4xl text-center">

                    <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-amber-400/20 bg-amber-400/10 px-4 py-2 text-sm font-medium text-amber-300">
                        <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                        Project-Based Workforce Management
                    </div>

                    <h1 class="text-5xl font-black tracking-tight text-white sm:text-6xl lg:text-7xl">
                        Track Work.
                        <span class="text-amber-400">Approve Time.</span>
                        <br>
                        Process Payroll.
                    </h1>

                    <p class="mx-auto mt-7 max-w-2xl text-lg leading-8 text-slate-400">
                        TimeTrack connects employees, projects, tasks, timesheets,
                        approvals and payroll into one simple workflow.
                    </p>

                    <div class="mt-10 flex flex-col justify-center gap-3 sm:flex-row">

                        @auth
                            <a
                                href="{{ url('/user') }}"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-400 px-6 py-3.5 font-semibold text-slate-950 shadow-xl shadow-amber-400/10 transition hover:-translate-y-0.5 hover:bg-amber-300"
                            >
                                Go to Dashboard

                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M5 12h14"/>
                                    <path d="m13 6 6 6-6 6"/>
                                </svg>
                            </a>
                        @else
                            <a
                                href="{{ url('/user/login') }}"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-400 px-6 py-3.5 font-semibold text-slate-950 shadow-xl shadow-amber-400/10 transition hover:-translate-y-0.5 hover:bg-amber-300"
                            >
                                Access TimeTrack

                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M5 12h14"/>
                                    <path d="m13 6 6 6-6 6"/>
                                </svg>
                            </a>
                        @endif

                        <a
                            href="#workflow"
                            class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-6 py-3.5 font-semibold text-white transition hover:bg-white/10"
                        >
                            Explore Workflow
                        </a>

                    </div>
                </div>


                {{-- Product Preview --}}
                <div class="mx-auto mt-16 max-w-6xl">

                    <div class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900 shadow-2xl shadow-black/40">

                        {{-- Browser Header --}}
                        <div class="flex items-center gap-2 border-b border-white/10 bg-slate-900 px-5 py-3">
                            <span class="h-3 w-3 rounded-full bg-red-400/80"></span>
                            <span class="h-3 w-3 rounded-full bg-yellow-400/80"></span>
                            <span class="h-3 w-3 rounded-full bg-green-400/80"></span>

                            <div class="ml-4 flex-1 rounded-lg bg-slate-800 px-4 py-2 text-xs text-slate-500">
                                timetrack.local/user
                            </div>
                        </div>

                        {{-- Dashboard Mockup --}}
                        <div class="grid lg:grid-cols-[210px_1fr]">

                            {{-- Sidebar --}}
                            <aside class="hidden border-r border-white/10 bg-slate-950 p-4 lg:block">

                                <div class="mb-7 flex items-center gap-2">
                                    <div class="h-7 w-7 rounded-lg bg-amber-400"></div>
                                    <span class="text-sm font-bold">TimeTrack</span>
                                </div>

                                <div class="space-y-1 text-xs">

                                    <div class="rounded-lg bg-amber-400/10 px-3 py-2 text-amber-300">
                                        Dashboard
                                    </div>

                                    <div class="px-3 py-2 text-slate-500">
                                        Employees
                                    </div>

                                    <div class="px-3 py-2 text-slate-500">
                                        Projects
                                    </div>

                                    <div class="px-3 py-2 text-slate-500">
                                        Tasks
                                    </div>

                                    <div class="px-3 py-2 text-slate-500">
                                        Time Entries
                                    </div>

                                    <div class="px-3 py-2 text-slate-500">
                                        Approvals
                                    </div>

                                    <div class="px-3 py-2 text-slate-500">
                                        Payroll
                                    </div>

                                </div>
                            </aside>

                            {{-- Dashboard --}}
                            <div class="p-6 lg:p-8">

                                <div class="mb-7 flex items-center justify-between">
                                    <div>
                                        <p class="text-xs text-slate-500">Monday, September 14, 2026</p>
                                        <h3 class="mt-1 text-xl font-bold">Good morning, Admin</h3>
                                    </div>

                                    <div class="hidden rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs text-slate-400 sm:block">
                                        September 2026
                                    </div>
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

                                    <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
                                        <p class="text-xs text-slate-500">Active Employees</p>
                                        <p class="mt-2 text-3xl font-bold">24</p>
                                        <p class="mt-2 text-xs text-emerald-400">+3 this month</p>
                                    </div>

                                    <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
                                        <p class="text-xs text-slate-500">Active Projects</p>
                                        <p class="mt-2 text-3xl font-bold">11</p>
                                        <p class="mt-2 text-xs text-blue-400">8 members avg.</p>
                                    </div>

                                    <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
                                        <p class="text-xs text-slate-500">Pending Timesheets</p>
                                        <p class="mt-2 text-3xl font-bold">17</p>
                                        <p class="mt-2 text-xs text-amber-400">Needs approval</p>
                                    </div>

                                    <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
                                        <p class="text-xs text-slate-500">Payroll</p>
                                        <p class="mt-2 text-3xl font-bold">₹4.82L</p>
                                        <p class="mt-2 text-xs text-slate-500">September</p>
                                    </div>

                                </div>

                                <div class="mt-6 grid gap-6 lg:grid-cols-3">

                                    <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5 lg:col-span-2">

                                        <div class="mb-5 flex items-center justify-between">
                                            <div>
                                                <h4 class="font-semibold">Timesheet Activity</h4>
                                                <p class="text-xs text-slate-500">
                                                    Current payroll period
                                                </p>
                                            </div>

                                            <span class="rounded-full bg-emerald-400/10 px-3 py-1 text-xs text-emerald-400">
                                                Live
                                            </span>
                                        </div>

                                        <div class="space-y-4">

                                            <div>
                                                <div class="mb-2 flex justify-between text-xs">
                                                    <span class="text-slate-400">Approved</span>
                                                    <span>78%</span>
                                                </div>

                                                <div class="h-2 overflow-hidden rounded-full bg-slate-800">
                                                    <div class="h-full w-[78%] rounded-full bg-emerald-400"></div>
                                                </div>
                                            </div>

                                            <div>
                                                <div class="mb-2 flex justify-between text-xs">
                                                    <span class="text-slate-400">Pending</span>
                                                    <span>14%</span>
                                                </div>

                                                <div class="h-2 overflow-hidden rounded-full bg-slate-800">
                                                    <div class="h-full w-[14%] rounded-full bg-amber-400"></div>
                                                </div>
                                            </div>

                                            <div>
                                                <div class="mb-2 flex justify-between text-xs">
                                                    <span class="text-slate-400">Rejected</span>
                                                    <span>8%</span>
                                                </div>

                                                <div class="h-2 overflow-hidden rounded-full bg-slate-800">
                                                    <div class="h-full w-[8%] rounded-full bg-red-400"></div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="rounded-xl border border-white/10 bg-white/[0.03] p-5">

                                        <h4 class="font-semibold">Payroll Summary</h4>

                                        <div class="mt-5 space-y-4">

                                            <div class="flex justify-between">
                                                <span class="text-sm text-slate-500">Regular</span>
                                                <span class="text-sm font-semibold">₹3,96,000</span>
                                            </div>

                                            <div class="flex justify-between">
                                                <span class="text-sm text-slate-500">Overtime</span>
                                                <span class="text-sm font-semibold">₹62,400</span>
                                            </div>

                                            <div class="flex justify-between">
                                                <span class="text-sm text-slate-500">Adjustments</span>
                                                <span class="text-sm font-semibold">₹24,000</span>
                                            </div>

                                            <div class="border-t border-white/10 pt-4">
                                                <div class="flex justify-between">
                                                    <span class="font-semibold">Net Pay</span>
                                                    <span class="font-bold text-amber-400">
                                                        ₹4,82,400
                                                    </span>
                                                </div>
                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>
                        </div>

                    </div>

                </div>

            </div>
        </section>


        {{-- Workflow --}}
        <section id="workflow" class="border-y border-white/10 bg-slate-900/40">

            <div class="mx-auto max-w-7xl px-6 py-20 lg:px-8">

                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-widest text-amber-400">
                        Core Workflow
                    </p>

                    <h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">
                        From work performed to payroll
                    </h2>

                    <p class="mt-4 text-slate-400">
                        Every stage is connected so payroll is based on verified,
                        approved work rather than unverified numbers.
                    </p>
                </div>


                <div class="mt-14 grid gap-5 md:grid-cols-3 lg:grid-cols-6">

                    @php
                        $workflow = [
                            ['01', 'Employee', 'Manage employee profiles and hourly rates.'],
                            ['02', 'Project', 'Assign employees to active projects.'],
                            ['03', 'Task', 'Create tasks and assign project members.'],
                            ['04', 'Time', 'Record working time against tasks.'],
                            ['05', 'Approval', 'Managers review and approve submitted time.'],
                            ['06', 'Payroll', 'Generate payroll from approved hours.'],
                        ];
                    @endphp

                    @foreach ($workflow as [$number, $title, $description])

                        <div class="relative rounded-2xl border border-white/10 bg-slate-950 p-5">

                            <span class="text-xs font-bold text-amber-400">
                                {{ $number }}
                            </span>

                            <h3 class="mt-4 font-semibold">
                                {{ $title }}
                            </h3>

                            <p class="mt-2 text-sm leading-6 text-slate-500">
                                {{ $description }}
                            </p>

                        </div>

                    @endforeach

                </div>

            </div>

        </section>


        {{-- Features --}}
        <section>

            <div class="mx-auto max-w-7xl px-6 py-20 lg:px-8">

                <div class="max-w-2xl">
                    <p class="text-sm font-semibold uppercase tracking-widest text-amber-400">
                        Platform Capabilities
                    </p>

                    <h2 class="mt-3 text-3xl font-bold sm:text-4xl">
                        Everything required for the complete workflow
                    </h2>

                    <p class="mt-4 text-slate-400">
                        TimeTrack focuses on the core requirements of
                        project-based employee time and payroll management.
                    </p>
                </div>


                <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-3">

                    @php
                        $features = [
                            [
                                'Employee Management',
                                'Manage employee identity, department, designation, manager, joining date, hourly rate and employment status.',
                            ],
                            [
                                'Project Management',
                                'Create projects, assign project managers and members, track project status, dates, budget and work summary.',
                            ],
                            [
                                'Task Management',
                                'Create tasks and subtasks, assign project members, define priorities, due dates and estimated hours.',
                            ],
                            [
                                'Time Tracking',
                                'Record date, start time, end time, breaks and calculated working minutes against specific project tasks.',
                            ],
                            [
                                'Approval Workflow',
                                'Project managers can review submitted time, approve valid work or reject entries with a reason.',
                            ],
                            [
                                'Payroll Processing',
                                'Generate payroll from approved regular and overtime hours with adjustments and deductions.',
                            ],
                        ];
                    @endphp

                    @foreach ($features as $feature)

                        <div class="group rounded-2xl border border-white/10 bg-white/[0.02] p-6 transition hover:-translate-y-1 hover:border-amber-400/30 hover:bg-white/[0.04]">

                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-400/10 text-amber-400">

                                <svg
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <path d="M12 3v18"/>
                                    <path d="M3 12h18"/>
                                </svg>

                            </div>

                            <h3 class="mt-5 font-semibold">
                                {{ $feature[0] }}
                            </h3>

                            <p class="mt-3 text-sm leading-6 text-slate-500">
                                {{ $feature[1] }}
                            </p>

                        </div>

                    @endforeach

                </div>

            </div>

        </section>


        {{-- Payroll Calculation --}}
        <section class="border-y border-white/10 bg-slate-900/40">

            <div class="mx-auto max-w-7xl px-6 py-20 lg:px-8">

                <div class="grid items-center gap-12 lg:grid-cols-2">

                    <div>

                        <p class="text-sm font-semibold uppercase tracking-widest text-amber-400">
                            Payroll Engine
                        </p>

                        <h2 class="mt-3 text-3xl font-bold sm:text-4xl">
                            Payroll starts with approved work
                        </h2>

                        <p class="mt-5 leading-7 text-slate-400">
                            TimeTrack does not simply calculate payroll from
                            fixed monthly values. Approved project time is the
                            foundation of payable hours.
                        </p>

                        <div class="mt-8 space-y-4">

                            <div class="flex gap-4">
                                <div class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-400"></div>
                                <div>
                                    <h3 class="font-semibold">Regular Pay</h3>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Approved regular hours × applicable hourly rate
                                    </p>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-amber-400"></div>
                                <div>
                                    <h3 class="font-semibold">Overtime Pay</h3>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Approved overtime hours × hourly rate × overtime multiplier
                                    </p>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-blue-400"></div>
                                <div>
                                    <h3 class="font-semibold">Final Net Pay</h3>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Gross pay + approved adjustments − deductions
                                    </p>
                                </div>
                            </div>

                        </div>

                    </div>


                    {{-- Calculation Card --}}
                    <div class="rounded-3xl border border-white/10 bg-slate-950 p-7 shadow-2xl">

                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs uppercase tracking-widest text-slate-500">
                                    Payroll Example
                                </p>

                                <h3 class="mt-1 text-xl font-bold">
                                    EMP001
                                </h3>
                            </div>

                            <span class="rounded-full bg-emerald-400/10 px-3 py-1 text-xs font-medium text-emerald-400">
                                Approved
                            </span>
                        </div>

                        <div class="mt-8 space-y-5">

                            <div class="flex justify-between">
                                <span class="text-slate-500">Hourly Rate</span>
                                <span class="font-semibold">₹250</span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-slate-500">Regular Hours</span>
                                <span class="font-semibold">32 hrs</span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-slate-500">Regular Pay</span>
                                <span class="font-semibold">₹8,000</span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-slate-500">Overtime</span>
                                <span class="font-semibold">4 hrs × 1.5</span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-slate-500">Overtime Pay</span>
                                <span class="font-semibold">₹1,500</span>
                            </div>

                            <div class="border-t border-white/10 pt-5">

                                <div class="flex justify-between">
                                    <span class="font-semibold">Gross Pay</span>
                                    <span class="font-bold">₹9,500</span>
                                </div>

                                <div class="mt-3 flex justify-between">
                                    <span class="text-slate-500">Deductions</span>
                                    <span class="text-red-400">− ₹500</span>
                                </div>

                            </div>

                            <div class="rounded-xl bg-amber-400/10 p-5">

                                <div class="flex items-center justify-between">

                                    <span class="font-semibold text-amber-200">
                                        Net Pay
                                    </span>

                                    <span class="text-2xl font-black text-amber-400">
                                        ₹9,000
                                    </span>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </section>


        {{-- Roles --}}
        <section>

            <div class="mx-auto max-w-7xl px-6 py-20 lg:px-8">

                <div class="mx-auto max-w-2xl text-center">

                    <p class="text-sm font-semibold uppercase tracking-widest text-amber-400">
                        Access Control
                    </p>

                    <h2 class="mt-3 text-3xl font-bold sm:text-4xl">
                        Built around clear responsibilities
                    </h2>

                </div>


                <div class="mt-12 grid gap-5 md:grid-cols-3">

                    @php
                        $roles = [
                            [
                                'Admin',
                                'Full system management',
                                ['Employees', 'Projects', 'Tasks', 'Rates', 'Payroll', 'System settings'],
                            ],
                            [
                                'Project Manager',
                                'Project-level control',
                                ['Assigned projects', 'Project tasks', 'Team members', 'Timesheet approval', 'Rejection workflow'],
                            ],
                            [
                                'Employee',
                                'Own work management',
                                ['Assigned projects', 'Assigned tasks', 'Time entries', 'Timesheet submission', 'Own records'],
                            ],
                        ];
                    @endphp

                    @foreach ($roles as [$role, $subtitle, $permissions])

                        <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-6">

                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-bold">
                                    {{ $role }}
                                </h3>

                                <span class="rounded-lg bg-white/5 px-2 py-1 text-xs text-slate-500">
                                    Role
                                </span>
                            </div>

                            <p class="mt-2 text-sm text-slate-500">
                                {{ $subtitle }}
                            </p>

                            <div class="mt-6 space-y-3">

                                @foreach ($permissions as $permission)

                                    <div class="flex items-center gap-3 text-sm text-slate-400">

                                        <svg
                                            class="h-4 w-4 shrink-0 text-emerald-400"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path d="m5 12 4 4L19 6"/>
                                        </svg>

                                        {{ $permission }}

                                    </div>

                                @endforeach

                            </div>

                        </div>

                    @endforeach

                </div>

            </div>

        </section>


        {{-- Security / Architecture --}}
        <section class="border-y border-white/10 bg-slate-900/40">

            <div class="mx-auto max-w-7xl px-6 py-20 lg:px-8">

                <div class="grid gap-12 lg:grid-cols-2">

                    <div>

                        <p class="text-sm font-semibold uppercase tracking-widest text-amber-400">
                            Architecture
                        </p>

                        <h2 class="mt-3 text-3xl font-bold sm:text-4xl">
                            Structured for maintainability
                        </h2>

                        <p class="mt-5 leading-7 text-slate-400">
                            TimeTrack keeps presentation, authorization,
                            business logic and persistence responsibilities
                            separated.
                        </p>

                    </div>


                    <div class="rounded-2xl border border-white/10 bg-slate-950 p-6">

                        <div class="space-y-3 font-mono text-sm">

                            <div class="rounded-lg bg-white/5 px-4 py-3 text-amber-300">
                                Filament UI
                            </div>

                            <div class="pl-8 text-slate-600">
                                ↓
                            </div>

                            <div class="rounded-lg bg-white/5 px-4 py-3 text-blue-300">
                                Shield / Permissions
                            </div>

                            <div class="pl-8 text-slate-600">
                                ↓
                            </div>

                            <div class="rounded-lg bg-white/5 px-4 py-3 text-purple-300">
                                Laravel Policies
                            </div>

                            <div class="pl-8 text-slate-600">
                                ↓
                            </div>

                            <div class="rounded-lg bg-white/5 px-4 py-3 text-emerald-300">
                                Services / Actions
                            </div>

                            <div class="pl-8 text-slate-600">
                                ↓
                            </div>

                            <div class="rounded-lg bg-white/5 px-4 py-3 text-slate-300">
                                Eloquent ORM
                            </div>

                            <div class="pl-8 text-slate-600">
                                ↓
                            </div>

                            <div class="rounded-lg bg-white/5 px-4 py-3 text-orange-300">
                                MySQL 8
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </section>


        {{-- Tech Stack --}}
        <section>

            <div class="mx-auto max-w-7xl px-6 py-20 lg:px-8">

                <div class="text-center">

                    <p class="text-sm font-semibold uppercase tracking-widest text-amber-400">
                        Technology
                    </p>

                    <h2 class="mt-3 text-3xl font-bold sm:text-4xl">
                        Modern PHP application stack
                    </h2>

                </div>


                <div class="mx-auto mt-12 grid max-w-4xl grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">

                    @foreach ([
                        'PHP 8.3+',
                        'Laravel 13',
                        'Filament 5',
                        'Livewire',
                        'Tailwind CSS 4',
                        'MySQL 8',
                        'Eloquent',
                        'Pest',
                        'Spatie Permission',
                        'Filament Shield',
                        'Policies',
                        'Git',
                    ] as $technology)

                        <div class="rounded-xl border border-white/10 bg-white/[0.02] px-4 py-4 text-center text-sm font-medium text-slate-300 transition hover:border-amber-400/30 hover:text-amber-300">
                            {{ $technology }}
                        </div>

                    @endforeach

                </div>

            </div>

        </section>


        {{-- Final CTA --}}
        <section class="relative overflow-hidden">

            <div class="mx-auto max-w-5xl px-6 py-24 text-center lg:px-8">

                <div class="absolute left-1/2 -z-10 h-64 w-64 -translate-x-1/2 rounded-full bg-amber-400/10 blur-3xl"></div>

                <p class="text-sm font-semibold uppercase tracking-widest text-amber-400">
                    TimeTrack
                </p>

                <h2 class="mt-4 text-4xl font-black tracking-tight sm:text-5xl">
                    One workflow.
                    <br>
                    Verified hours.
                    <br>
                    Accurate payroll.
                </h2>

                <p class="mx-auto mt-6 max-w-xl text-slate-400">
                    A focused project-based employee time and payroll
                    management system designed around approved work.
                </p>

                <div class="mt-9">

                    <a
                        href="{{ url('/user/login') }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-amber-400 px-7 py-3.5 font-semibold text-slate-950 transition hover:bg-amber-300"
                    >
                        Enter TimeTrack

                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14"/>
                            <path d="m13 6 6 6-6 6"/>
                        </svg>
                    </a>

                </div>

            </div>

        </section>

    </main>


    {{-- Footer --}}
    <footer class="border-t border-white/10">

        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-6 py-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between lg:px-8">

            <div>
                <span class="font-semibold text-slate-300">TimeTrack</span>
                <span class="mx-2">·</span>
                Project-Based Employee Time & Payroll Management
            </div>

            <div>
                Laravel {{ Illuminate\Foundation\Application::VERSION }}
                · PHP {{ PHP_VERSION }}
            </div>

        </div>

    </footer>

</body>
</html>