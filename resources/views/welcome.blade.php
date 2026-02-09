<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>

    <meta name="application-name" content="{{ config('app.name') }}"/>
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace+SC:wght@400;700&amp;display=swap" rel="stylesheet">

    <title>{{ config('app.name') }}</title>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    @filamentStyles
    @vite('resources/css/app.css')
</head> 

<body>

<div class="mt-8 flex justify-center">
    <div name="content" class="sm:w-1 md:w-1/2">
        <div class="flex justify-center p-6">
            <img src="{{ asset('/img/logo-OpenIRM.png') }}" width="10%" alt="OpenIRM Logo">

        </div>
        <h1 class="mb-4 text-4xl font-extrabold leading-none tracking-tight text-gray-900 md:text-3xl lg:text-3xl dark:text-white text-center"
            style="font-family: 'Bruno Ace SC', sans-serif;">Welcome</h1>
        <div class="mt-12">
            <p>Welcome to the <strong> National CII Governance & Cybersecurity Management Platform</strong>, developed under <strong>WARDIP</strong> and led by the <strong>Ministry of Communications & Digital Economy (MOCDE)</strong>.</p>
            <p>This platform has been established to strengthen cybersecurity management and ensure the protection of The Gambia’s Critical Information Infrastructure (CII). It provides a collaborative environment where stakeholders can assess risks, manage critical assets, monitor overall progress through dashboards, and ensure that security controls and implementations are effectively applied.</p>
            <p class="mt-6">By bringing these capabilities together, the platform enables sustainable governance, continuous improvement, and coordinated action across all sectors that deliver essential services to the nation.</p>
<p class="mt-6"><strong>Please log in to begin</strong></p>
        </div>
        <div class="text-center mt-12">
            <a href="/app" class="bg-primary-500 p-3 rounded" id="login-button">Login</a>
        </div>

    </div>
</div>

@livewire('notifications')

@filamentScripts
@vite('resources/js/app.js')
</body>
</html>









