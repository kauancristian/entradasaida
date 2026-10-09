<?php
require_once __DIR__ . '/../model/select_model.php';
require_once __DIR__ . '/../model/sessions.php';
$select = new select_model();
$sectionInicial = $_GET['section'] ?? 'entrada';
$sectionsPermitidas = ['inicio', 'entrada', 'saida', 'estagio', 'relatorios', 'relatorio-entrada', 'relatorio-saida', 'relatorio-estagio', 'relatorio-dia', 'qrcode', 'ultimas-saidas', 'atrasos', 'cadastro'];
if (!in_array($sectionInicial, $sectionsPermitidas, true)) {
    $sectionInicial = 'entrada';
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sistema Escolar Salaberga</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://unpkg.com/html5-qrcode"></script>
<script>tailwind={config:{theme:{extend:{colors:{'ceara-green':'#008C45','ceara-light-green':'#3CB371','ceara-olive':'#8CA03E','ceara-orange':'#FFA500',primary:'#4CAF50',secondary:'#FFB74D',danger:'#dc3545',admin:'#0dcaf0',grey:'#6c757d',info:'#4169E1'}},fontFamily:{inter:['Inter','sans-serif']}}}};</script>
<script src="https://cdn.tailwindcss.com"></script>
<style>
:root{--salaberga-dark:#064c2b;--salaberga-green:#008c45;--salaberga-active:#276448;--salaberga-amber:#f3a11a;--salaberga-workspace:#eaf3e8;--salaberga-ink:#14251c;--salaberga-muted:#64736a}
*{box-sizing:border-box}
html,body{margin:0;min-height:100%;font-family:'Inter',sans-serif;color:var(--salaberga-ink);background:var(--salaberga-workspace)}
.sidebar{position:fixed;inset:0 auto 0 0;z-index:1000;width:256px;display:flex;flex-direction:column;padding:25px 16px 18px;background:var(--salaberga-dark);color:#f5fff8}
.brand{display:flex;align-items:center;gap:11px;margin:2px 7px 35px;color:#fff;text-decoration:none;font-size:15px;font-weight:700}.brand-mark{display:grid;width:40px;height:40px;place-items:center;border:1px solid #ffffff55;border-radius:11px;color:#ffc04d;font-size:20px}.brand small{display:block;margin-top:4px;color:#b6d4c1;font-size:10px;font-weight:400}
.sidebar>nav{flex:1;min-height:0;overflow-y:auto;overscroll-behavior:contain;scrollbar-width:thin;scrollbar-color:#3c7254 transparent}.nav-group{margin-bottom:20px}.nav-label{margin:0 10px 8px;color:#8eb9a0;font-size:9px;font-weight:700;letter-spacing:.8px;text-transform:uppercase}.nav-group>summary.nav-label{display:flex;align-items:center;justify-content:space-between;cursor:pointer;list-style:none}.nav-group>summary.nav-label::-webkit-details-marker{display:none}.nav-group>summary.nav-label::after{width:6px;height:6px;margin-right:3px;border-right:1.5px solid currentColor;border-bottom:1.5px solid currentColor;content:'';transform:rotate(45deg);transition:transform .18s ease}.nav-group[open]>summary.nav-label::after{transform:rotate(225deg)}.nav-group>summary.nav-label:focus-visible{outline:2px solid var(--salaberga-amber);outline-offset:3px}.nav-item{position:relative;width:100%;min-height:40px;display:flex;align-items:center;gap:12px;margin:3px 0;padding:0 11px;border:0;border-radius:7px;background:transparent;color:#e1f0e6;text-align:left;font:600 12px 'Inter',sans-serif;cursor:pointer}.nav-item i{width:16px;color:#b8d7c3;text-align:center}.nav-item:hover{background:#ffffff1a;color:#fff}.nav-item.active{background:var(--salaberga-active);color:#ffc04d}.nav-item.active:before{position:absolute;inset:8px auto 8px 0;width:3px;border-radius:0 3px 3px 0;background:var(--salaberga-amber);content:''}.nav-item.active i{color:#ffc04d}.sidebar-bottom{margin-top:auto}.nav-item.logout{color:#f0d5ce}.nav-item.logout i{color:#f1aa91}
.nav-group>summary.nav-label{min-height:34px;margin:0 2px 8px;padding:0 10px;border:1px solid #ffffff24;border-radius:6px;background:#ffffff0a;color:#c5dfce;font-size:10px;transition:background .18s ease,color .18s ease}.nav-group>summary.nav-label:hover{background:#ffffff16;color:#fff}.nav-item{font-size:13px}.nav-item i{color:#c5dfce}.nav-item:focus-visible{outline:2px solid var(--salaberga-amber);outline-offset:2px}.nav-group[open]>.nav-item{animation:sidebar-submenu-in .2s ease both}.nav-group[open]>.nav-item:nth-child(3){animation-delay:35ms}.nav-group[open]>.nav-item:nth-child(4){animation-delay:70ms}.nav-group[open]>.nav-item:nth-child(5){animation-delay:105ms}.nav-group[open]>.nav-item:nth-child(6){animation-delay:140ms}@keyframes sidebar-submenu-in{from{opacity:0;transform:translateY(-5px)}to{opacity:1;transform:translateY(0)}}
.main-content{min-height:100vh;margin-left:256px;padding:34px clamp(20px,4vw,60px);}.page-section{display:none;max-width:1200px;margin:0 auto}.page-section.active{display:block}.section-title{margin:0 0 22px;color:#08723a;font-size:25px}.section-lead{margin:-14px 0 22px;color:var(--salaberga-muted);font-size:13px}.page-section [class*="max-w-"]{max-width:100%}.page-section .container{width:100%;max-width:100%;margin-left:auto;margin-right:auto}.page-section .fixed{z-index:900}
#relatorio-entrada > .main-content{width:100%;min-height:0;margin-left:0;padding:0}
#relatorio-estagio > .main-content{width:100%;min-height:0;margin-left:0;padding:0}
#ultimas-saidas{width:calc(100% + min(16px,1.5vw) - 40px);max-width:none;margin-left:12px;margin-right:calc(-1 * min(16px,1.5vw))}
#ultimas-saidas .main-container{width:100%;max-width:none}
.page-section [id$="Modal"]:not([id$="ModalContent"]){z-index:1100;max-height:100dvh;padding:16px;overflow-y:auto;overscroll-behavior:contain}
.page-section [id$="ModalContent"]{width:min(100%,28rem);height:min(520px,calc(100dvh - 32px));max-height:calc(100dvh - 32px);margin:0;overflow-y:auto;overscroll-behavior:contain;scrollbar-gutter:stable;display:flex;flex-direction:column;justify-content:center}
@media(max-width:1100px){#ultimas-saidas{width:100%;margin-left:0;margin-right:0}}
.page-section .bg-white{background:#fff}.page-section .rounded-xl,.page-section .rounded-2xl{border-radius:8px}.page-section .shadow-lg,.page-section .shadow-md{box-shadow:0 2px 7px #143b2114}
.page-section .bg-gradient-to-r.from-ceara-green.to-ceara-light-green{background:linear-gradient(100deg,var(--salaberga-dark),var(--salaberga-green))!important}
.page-section .bg-gray-50,.page-section .bg-slate-50,.page-section .bg-gray-100{background:#f4f8f3}.page-section .text-ceara-green,.page-section .text-green-600,.page-section .text-green-700{color:var(--salaberga-green)}.page-section .bg-ceara-green,.page-section .bg-green-600,.page-section .bg-green-700{background-color:var(--salaberga-green)}
.mobile-menu,.sidebar-close,.sidebar-scrim{display:none}
@media(max-width:760px){.sidebar{width:min(290px,84vw);transform:translateX(-102%);transition:transform .22s ease}.sidebar-open .sidebar{transform:translateX(0)}.sidebar-close{position:absolute;top:20px;right:14px;display:grid;width:34px;height:34px;place-items:center;border:1px solid #ffffff44;border-radius:7px;background:transparent;color:white}.brand{margin-right:38px}.mobile-menu{position:fixed;top:10px;left:12px;z-index:999;display:grid;width:38px;height:38px;place-items:center;border:0;border-radius:7px;background:var(--salaberga-dark);color:#fff}.sidebar-scrim{position:fixed;inset:0;z-index:998;display:none;border:0;background:#081f126b}.sidebar-open .sidebar-scrim{display:block}.main-content{margin-left:0;padding:62px 15px 24px}#ultimas-saidas{width:100%;margin-left:0;margin-right:0}.page-section [id$="Modal"]:not([id$="ModalContent"]){padding:12px}.page-section [id$="ModalContent"]{height:min(520px,calc(100dvh - 24px));max-height:calc(100dvh - 24px);padding:24px}}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{scroll-behavior:auto!important;transition-duration:.01ms!important}.nav-group[open]>.nav-item{animation:none!important}}

    * {
      font-family: 'Inter', sans-serif;
    }

    .menu-card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      transition: all 0.3s ease;
    }

    .menu-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .menu-icon {
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 12px;
      transition: all 0.3s ease;
    }

    .menu-card:hover .menu-icon {
      transform: scale(1.1);
    }

    .gradient-text {
      background: linear-gradient(45deg, #008C45, #3CB371);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .header {
      background: linear-gradient(90deg, #008C45, #3CB371);
    }

    .footer {
      background: white;
      border-top: 1px solid #e5e7eb;
    }

    .footer::before {
      content: '';
      position: absolute;
      top: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 100px;
      height: 2px;
      background: linear-gradient(70deg, #008C45,rgb(225, 130, 6));
    }
  

        * {
            font-family: 'Inter', sans-serif;
        }

        .form-input:focus {
            outline: none;
            border-color: #008C45;
            box-shadow: 0 0 0 3px rgba(0, 140, 69, 0.1);
        }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 1rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 3rem;
        }

        .form-select:focus {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23008C45' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        }

        .required-field::after {
            content: ' *';
            color: #dc3545;
            font-weight: bold;
        }

        /* Custom Select Styles */
        .custom-select-container {
            position: relative;
            width: 100%;
        }

        .select-trigger {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            min-height: 3.5rem;
        }

        .select-trigger:hover {
            border-color: #008C45;
        }

        .select-trigger.active {
            border-color: #008C45;
            box-shadow: 0 0 0 3px rgba(0, 140, 69, 0.1);
        }

        .select-placeholder {
            color: #9ca3af;
            font-weight: 500;
        }

        .select-arrow {
            color: #6b7280;
            transition: transform 0.3s ease;
        }

        .select-trigger.active .select-arrow {
            transform: rotate(180deg);
        }

        .select-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 0.5rem 0.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            max-height: 300px;
            overflow: hidden;
            display: none;
        }

        .select-dropdown.active {
            display: block;
        }

        .search-container {
            position: relative;
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .search-input {
            width: 100%;
            padding: 0.5rem 2rem 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            outline: none;
        }

        .search-input:focus {
            border-color: #008C45;
            box-shadow: 0 0 0 2px rgba(0, 140, 69, 0.1);
        }

        .search-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 0.875rem;
        }

        .options-container {
            max-height: 200px;
            overflow-y: auto;
        }

        .select-option {
            padding: 0.75rem 1rem;
            cursor: pointer;
            transition: background-color 0.2s ease;
            border-bottom: 1px solid #f3f4f6;
        }

        .select-option:hover {
            background-color: #f9fafb;
        }

        .select-option.selected {
            background-color: #008C45;
            color: white;
        }

        .select-option.hidden {
            display: none;
        }

        .hidden-select {
            display: none;
        }

        /* Scrollbar personalizada */
        .options-container::-webkit-scrollbar {
            width: 6px;
        }

        .options-container::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .options-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .options-container::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    

        * {
            font-family: 'Inter', sans-serif;
        }

        .form-input:focus {
            outline: none;
            border-color: #008C45;
            box-shadow: 0 0 0 3px rgba(0, 140, 69, 0.1);
        }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 1rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 3rem;
        }

        .form-select:focus {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23008C45' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        }

        .required-field::after {
            content: ' *';
            color: #dc3545;
            font-weight: bold;
        }

        /* Custom Select Styles */
        .custom-select-container {
            position: relative;
            width: 100%;
        }

        .select-trigger {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            min-height: 3.5rem;
        }

        .select-trigger:hover {
            border-color: #008C45;
        }

        .select-trigger.active {
            border-color: #008C45;
            box-shadow: 0 0 0 3px rgba(0, 140, 69, 0.1);
        }

        .select-placeholder {
            color: #9ca3af;
            font-weight: 500;
        }

        .select-arrow {
            color: #6b7280;
            transition: transform 0.3s ease;
        }

        .select-trigger.active .select-arrow {
            transform: rotate(180deg);
        }

        .select-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 0.5rem 0.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            max-height: 300px;
            overflow: hidden;
            display: none;
        }

        .select-dropdown.active {
            display: block;
        }

        .search-container {
            position: relative;
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .search-input {
            width: 100%;
            padding: 0.5rem 2rem 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            outline: none;
        }

        .search-input:focus {
            border-color: #008C45;
            box-shadow: 0 0 0 2px rgba(0, 140, 69, 0.1);
        }

        .search-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 0.875rem;
        }

        .options-container {
            max-height: 200px;
            overflow-y: auto;
        }

        .select-option {
            padding: 0.75rem 1rem;
            cursor: pointer;
            transition: background-color 0.2s ease;
            border-bottom: 1px solid #f3f4f6;
        }

        .select-option:hover {
            background-color: #f9fafb;
        }

        .select-option.selected {
            background-color: #008C45;
            color: white;
        }

        .select-option.hidden {
            display: none;
        }

        .hidden-select {
            display: none;
        }

        /* Scrollbar personalizada */
        .options-container::-webkit-scrollbar {
            width: 6px;
        }

        .options-container::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .options-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .options-container::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    

        * {
            font-family: 'Inter', sans-serif;
        }

        .header {
            background: linear-gradient(90deg, #008C45, #3CB371);
        }

        .gradient-text {
            background: linear-gradient(45deg, #008C45, #3CB371);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }

        .input-focus-ring:focus {
            box-shadow: 0 0 0 3px rgba(0, 140, 69, 0.1);
        }

        .btn-primary {
            background: linear-gradient(90deg, #008C45, #3CB371);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, #006d35, #2e8b57);
            transform: translateY(-1px);
            box-shadow: 0 6px 12px -2px rgba(0, 140, 69, 0.2);
        }

        .loading-spinner {
            border: 2px solid #f3f3f3;
            border-top: 2px solid #008C45;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .footer {
            background: white;
            border-top: 1px solid #e5e7eb;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 2px;
            background: linear-gradient(70deg, #008C45, #FFA500);
        }

        .icon-container {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: rgba(0, 140, 69, 0.1);
            color: #008C45;
        }

        .slide-in {
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .shake {
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .success-message {
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Custom dropdown styles */
        select:focus + div i {
            transform: rotate(180deg);
        }

        select:hover + div i {
            color: #008C45;
        }

        /* Remove default select styling */
        select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        /* Custom option styling */
        select option {
            padding: 12px;
            font-size: 14px;
        }

        select option:checked {
            background: linear-gradient(90deg, #008C45, #3CB371);
            color: white;
        }

        /* Select2 Custom Styling */
        .select2-container--default .select2-selection--single {
            height: auto !important;
            padding: 16px !important;
            border: 2px solid #e5e7eb !important;
            border-radius: 8px !important;
            background-color: white !important;
            font-size: 16px !important;
        }

        .select2-container--default .select2-selection--single:focus,
        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #008C45 !important;
            box-shadow: 0 0 0 3px rgba(0, 140, 69, 0.1) !important;
        }

        .select2-container--default .select2-selection--single:hover {
            border-color: #d1d5db !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #374151 !important;
            line-height: normal !important;
            padding: 0 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #9ca3af !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100% !important;
            right: 12px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow b {
            border-color: #9ca3af transparent transparent transparent !important;
            border-width: 5px 4px 0 4px !important;
        }

        .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
            border-color: transparent transparent #9ca3af transparent !important;
            border-width: 0 4px 5px 4px !important;
        }

        .select2-dropdown {
            border: 2px solid #e5e7eb !important;
            border-radius: 8px !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            background-color: white !important;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field {
            border: 2px solid #e5e7eb !important;
            border-radius: 6px !important;
            padding: 8px 12px !important;
            font-size: 14px !important;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field:focus {
            border-color: #008C45 !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(0, 140, 69, 0.1) !important;
        }

        .select2-container--default .select2-results__option {
            padding: 12px 16px !important;
            font-size: 14px !important;
            color: #374151 !important;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #008C45 !important;
            color: white !important;
        }

        .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #f3f4f6 !important;
            color: #374151 !important;
        }

        /* Modal Animations */
        .modal-enter {
            animation: modalEnter 0.3s ease-out forwards;
        }

        .modal-exit {
            animation: modalExit 0.3s ease-in forwards;
        }

        @keyframes modalEnter {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        @keyframes modalExit {
            from {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
            to {
                opacity: 0;
                transform: scale(0.9) translateY(-20px);
            }
        }

        .modal-backdrop {
            animation: backdropEnter 0.3s ease-out forwards;
        }

        @keyframes backdropEnter {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
    

        * {
            font-family: 'Inter', sans-serif;
        }

        .gradient-text {
            background: linear-gradient(45deg, #008C45, #3CB371);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header {
            background: linear-gradient(90deg, #008C45, #3CB371);
        }

        .report-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
        }

        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .report-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .report-card:hover .report-icon {
            transform: scale(1.1);
        }

        .footer {
            background: white;
            border-top: 1px solid #e5e7eb;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 2px;
            background: linear-gradient(70deg, #008C45, rgb(225, 130, 6));
        }
    

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        .header {
            background: linear-gradient(90deg, #008C45, #3CB371);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .header-title {
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-nav {
            display: flex;
            gap: 12px;
        }

        .header-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background-color: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            color: white;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .header-btn:hover {
            background-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-1px);
        }

        .main-content {
            flex: 1;
            margin-top: 64px;
            padding: 32px 16px 80px;
        }

        .container {
            max-width: 768px;
            margin: 0 auto;
        }

        .title-section {
            text-align: center;
            margin-bottom: 32px;
        }

        .icon-container {
            width: 48px;
            height: 48px;
            background: rgba(0, 140, 69, 0.1);
            color: #008C45;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .gradient-text {
            background: linear-gradient(45deg, #008C45, #3CB371);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .form-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .tabs-nav {
            display: flex;
            border-bottom: 1px solid #e5e7eb;
        }

        .tab-btn {
            flex: 1;
            padding: 16px;
            background: none;
            border: none;
            font-weight: 500;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 2px solid transparent;
        }

        .tab-btn.active {
            color: #008C45;
            border-bottom-color: #008C45;
        }

        .tab-content {
            padding: 24px;
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
            font-size: 0.875rem;
        }

        .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.875rem;
            background: white;
            transition: all 0.3s ease;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23374151' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
            cursor: pointer;
        }

        .form-select:focus {
            outline: none;
            border-color: #008C45;
            box-shadow: 0 0 0 3px rgba(0, 140, 69, 0.1);
        }

        .form-select:hover {
            border-color: #d1d5db;
        }

        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .radio-item {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 8px 0;
            transition: all 0.3s ease;
        }

        .radio-item:hover {
            background: #f9fafb;
            border-radius: 6px;
            padding: 8px 12px;
            margin: 0 -12px;
        }

        .radio-input {
            position: absolute;
            opacity: 0;
        }

        .radio-custom {
            width: 20px;
            height: 20px;
            border: 2px solid #d1d5db;
            border-radius: 50%;
            margin-right: 12px;
            position: relative;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .radio-input:checked + .radio-custom {
            border-color: #008C45;
        }

        .radio-custom::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 10px;
            height: 10px;
            background: #008C45;
            border-radius: 50%;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .radio-input:checked + .radio-custom::after {
            opacity: 1;
        }

        .radio-label {
            font-size: 0.875rem;
            color: #374151;
            font-weight: 400;
        }

        .btn-primary {
            width: 100%;
            background: linear-gradient(90deg, #008C45, #3CB371);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, #006d35, #2e8b57);
            transform: translateY(-1px);
            box-shadow: 0 6px 12px -2px rgba(0, 140, 69, 0.2);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .info-card {
            background: #dbeafe;
            border: 1px solid #93c5fd;
            border-radius: 8px;
            padding: 16px;
            display: flex;
            gap: 12px;
        }

        .info-icon {
            color: #2563eb;
            flex-shrink: 0;
        }

        .info-content h3 {
            font-weight: 500;
            color: #1e40af;
            margin-bottom: 4px;
            font-size: 0.875rem;
        }

        .info-content p {
            color: #1e40af;
            font-size: 0.75rem;
            line-height: 1.4;
        }

        .footer {
            background: white;
            border-top: 1px solid #e5e7eb;
            padding: 16px;
            text-align: center;
            color: #6b7280;
            font-size: 0.75rem;
            position: relative;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 2px;
            background: linear-gradient(70deg, #008C45, #FFA500);
        }

        /* Select2 Custom Styling */
        .select2-container--default .select2-selection--single {
            height: 44px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            transition: all 0.3s ease;
            background: white;
        }

        .select2-container--default .select2-selection--single:hover {
            border-color: #d1d5db;
            background-color: #f9fafb;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 40px;
            padding-left: 12px;
            color: #374151;
            font-size: 0.875rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
            right: 12px;
            transition: transform 0.3s ease;
        }

        .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow {
            transform: rotate(180deg);
        }

        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #008C45;
            box-shadow: 0 0 0 3px rgba(0, 140, 69, 0.1);
            background-color: white;
        }

        .select2-dropdown {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            margin-top: 4px;
        }

        .select2-container--default .select2-results__option {
            padding: 12px 16px;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .select2-container--default .select2-results__option:hover {
            background-color: #f3f4f6;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #008C45;
            color: white;
        }

        .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #f3f4f6;
            color: #374151;
            font-weight: 500;
        }

        .select2-search--dropdown {
            padding: 8px;
        }

        .select2-search--dropdown .select2-search__field {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .select2-search--dropdown .select2-search__field:hover {
            border-color: #9ca3af;
        }

        .select2-search--dropdown .select2-search__field:focus {
            border-color: #008C45;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 140, 69, 0.1);
        }

        .select2-container--default .select2-selection--single .select2-selection__clear {
            color: #6b7280;
            margin-right: 32px;
            font-size: 1.25rem;
            transition: color 0.2s ease;
        }

        .select2-container--default .select2-selection--single .select2-selection__clear:hover {
            color: #ef4444;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #9ca3af;
        }

        /* Loading state for Select2 */
        .select2-container--default.select2-container--loading .select2-selection--single {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23008C45' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 40px center;
            background-size: 16px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 640px) {
            .header-nav {
                gap: 8px;
            }
            
            .header-btn span {
                display: none;
            }
            
            .tabs-nav {
                flex-direction: column;
            }
            
            .tab-btn {
                text-align: left;
                border-bottom: 1px solid #e5e7eb;
                border-right: none;
            }
            
            .tab-btn.active {
                border-bottom-color: #e5e7eb;
                border-left: 3px solid #008C45;
                background: #f9fafb;
            }
        }
    

        * {
            font-family: 'Inter', sans-serif;
        }

        .gradient-text {
            background: linear-gradient(45deg, #008C45, #3CB371);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header {
            background: linear-gradient(90deg, #008C45, #3CB371);
        }

        .report-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        .report-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, #008C45, #3CB371);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 140, 69, 0.2), 0 4px 6px -2px rgba(0, 140, 69, 0.1);
        }

        .report-card:hover::before {
            opacity: 1;
        }

        .select-field {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            background-color: white;
            color: #374151;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
        }

        .select-field:focus {
            outline: none;
            border-color: #008C45;
            box-shadow: 0 0 0 3px rgba(0, 140, 69, 0.1);
        }

        .radio-group {
            display: flex;
            gap: 1rem;
            margin: 1rem 0;
            justify-content: center;
            flex-wrap: wrap;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            min-width: 140px;
            justify-content: center;
        }

        .radio-option:hover {
            border-color: #008C45;
            background-color: #f0fdf4;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(0, 140, 69, 0.15);
        }

        .radio-option input[type="radio"] {
            accent-color: #008C45;
            width: 16px;
            height: 16px;
        }

        .radio-option span {
            font-weight: 500;
            color: #374151;
        }

        .btn-submit {
            background: linear-gradient(45deg, #008C45, #3CB371);
            color: white;
            padding: 0.875rem 1.75rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            width: 100%;
            max-width: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 140, 69, 0.2);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-submit i {
            font-size: 1.1rem;
        }

        .footer {
            background: white;
            border-top: 1px solid #e5e7eb;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 2px;
            background: linear-gradient(70deg, #008C45, rgb(225, 130, 6));
        }

        .card-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .card-icon.aluno {
            background: #E8F5E9;
            color: #008C45;
        }

        .card-icon.ano {
            background: #E3F2FD;
            color: #1976D2;
        }

        .card-icon.turma {
            background: #FFF3E0;
            color: #FF9800;
        }

        @media (max-width: 640px) {
            .radio-group {
                flex-direction: column;
                align-items: stretch;
            }

            .radio-option {
                width: 100%;
            }
        }
    

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        .header {
            background: linear-gradient(90deg, #008C45, #3CB371);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .header-title {
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-nav {
            display: flex;
            gap: 12px;
        }

        .header-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background-color: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            color: white;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .header-btn:hover {
            background-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-1px);
        }

        .main-content {
            flex: 1;
            margin-top: 64px;
            padding: 32px 16px 80px;
        }

        .container {
            max-width: 768px;
            margin: 0 auto;
        }

        .title-section {
            text-align: center;
            margin-bottom: 32px;
        }

        .icon-container {
            width: 48px;
            height: 48px;
            background: rgba(0, 140, 69, 0.1);
            color: #008C45;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .gradient-text {
            background: linear-gradient(45deg, #008C45, #3CB371);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .form-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .tabs-nav {
            display: flex;
            border-bottom: 1px solid #e5e7eb;
        }

        .tab-btn {
            flex: 1;
            padding: 16px;
            background: none;
            border: none;
            font-weight: 500;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 2px solid transparent;
        }

        .tab-btn.active {
            color: #008C45;
            border-bottom-color: #008C45;
        }

        .tab-content {
            padding: 24px;
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
            font-size: 0.875rem;
        }

        .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.875rem;
            background: white;
            transition: all 0.3s ease;
        }

        .form-select:focus {
            outline: none;
            border-color: #008C45;
            box-shadow: 0 0 0 3px rgba(0, 140, 69, 0.1);
        }

        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .radio-item {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .radio-input {
            position: absolute;
            opacity: 0;
        }

        .radio-custom {
            width: 20px;
            height: 20px;
            border: 2px solid #d1d5db;
            border-radius: 50%;
            margin-right: 12px;
            position: relative;
            transition: all 0.3s ease;
        }

        .radio-input:checked + .radio-custom {
            border-color: #008C45;
        }

        .radio-custom::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 10px;
            height: 10px;
            background: #008C45;
            border-radius: 50%;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .radio-input:checked + .radio-custom::after {
            opacity: 1;
        }

        .radio-label {
            font-size: 0.875rem;
            color: #374151;
        }

        .btn-primary {
            width: 100%;
            background: linear-gradient(90deg, #008C45, #3CB371);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, #006d35, #2e8b57);
            transform: translateY(-1px);
            box-shadow: 0 6px 12px -2px rgba(0, 140, 69, 0.2);
        }

        .info-card {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 8px;
            padding: 16px;
            display: flex;
            gap: 12px;
        }

        .info-icon {
            color: #d97706;
            flex-shrink: 0;
        }

        .info-content h3 {
            font-weight: 500;
            color: #92400e;
            margin-bottom: 4px;
            font-size: 0.875rem;
        }

        .info-content p {
            color: #92400e;
            font-size: 0.75rem;
            line-height: 1.4;
        }

        .footer {
            background: white;
            border-top: 1px solid #e5e7eb;
            padding: 16px;
            text-align: center;
            color: #6b7280;
            font-size: 0.75rem;
            position: relative;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 2px;
            background: linear-gradient(70deg, #008C45, #FFA500);
        }

        /* Select2 Custom Styling */
        .select2-container--default .select2-selection--single {
            height: 44px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            transition: all 0.3s ease;
            background: white;
        }

        .select2-container--default .select2-selection--single:hover {
            border-color: #d1d5db;
            background-color: #f9fafb;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 40px;
            padding-left: 12px;
            color: #374151;
            font-size: 0.875rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
            right: 12px;
            transition: transform 0.3s ease;
        }

        .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow {
            transform: rotate(180deg);
        }

        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #008C45;
            box-shadow: 0 0 0 3px rgba(0, 140, 69, 0.1);
            background-color: white;
        }

        .select2-dropdown {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            margin-top: 4px;
        }

        .select2-container--default .select2-results__option {
            padding: 12px 16px;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .select2-container--default .select2-results__option:hover {
            background-color: #f3f4f6;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #008C45;
            color: white;
        }

        .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #f3f4f6;
            color: #374151;
            font-weight: 500;
        }

        .select2-search--dropdown {
            padding: 8px;
        }

        .select2-search--dropdown .select2-search__field {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .select2-search--dropdown .select2-search__field:hover {
            border-color: #9ca3af;
        }

        .select2-search--dropdown .select2-search__field:focus {
            border-color: #008C45;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 140, 69, 0.1);
        }

        .select2-container--default .select2-selection--single .select2-selection__clear {
            color: #6b7280;
            margin-right: 32px;
            font-size: 1.25rem;
            transition: color 0.2s ease;
        }

        .select2-container--default .select2-selection--single .select2-selection__clear:hover {
            color: #ef4444;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #9ca3af;
        }

        /* Loading state for Select2 */
        .select2-container--default.select2-container--loading .select2-selection--single {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23008C45' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 40px center;
            background-size: 16px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 640px) {
            .header-nav {
                gap: 8px;
            }
            
            .header-btn span {
                display: none;
            }
            
            .tabs-nav {
                flex-direction: column;
            }
            
            .tab-btn {
                text-align: left;
                border-bottom: 1px solid #e5e7eb;
                border-right: none;
            }
            
            .tab-btn.active {
                border-bottom-color: #e5e7eb;
                border-left: 3px solid #008C45;
                background: #f9fafb;
            }
        }
    

    * {
      font-family: 'Inter', sans-serif;
    }

    .form-card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      transition: all 0.3s ease;
    }

    .form-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .gradient-text {
      background: linear-gradient(45deg, #008C45, #3CB371);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .header {
      background: linear-gradient(90deg, #008C45, #3CB371);
    }

    .footer {
      background: white;
      border-top: 1px solid #e5e7eb;
    }

    .footer::before {
      content: '';
      position: absolute;
      top: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 100px;
      height: 2px;
      background: linear-gradient(70deg, #008C45, #FFA500);
    }

    input[type="date"] {
      border: 1px solid #d1d5db;
      border-radius: 8px;
      padding: 8px 12px;
      width: 100%;
      max-width: 200px;
      transition: all 0.3s ease;
    }

    input[type="date"]:focus {
      outline: none;
      border-color: #3CB371;
      box-shadow: 0 0 0 3px rgba(60, 179, 113, 0.2);
    }

    button {
      background: linear-gradient(45deg, #008C45, #3CB371);
      color: white;
      border: none;
      border-radius: 8px;
      padding: 10px 20px;
      font-weight: 500;
      transition: all 0.3s ease;
    }

    button:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
  

        * {
            font-family: 'Inter', sans-serif;
        }

        .header {
            background: linear-gradient(90deg, #008C45, #3CB371);
        }

        .gradient-text {
            background: linear-gradient(45deg, #008C45, #3CB371);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }

        .input-focus-ring:focus {
            box-shadow: 0 0 0 3px rgba(0, 140, 69, 0.1);
        }

        .btn-primary {
            background: linear-gradient(90deg, #008C45, #3CB371);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, #006d35, #2e8b57);
            transform: translateY(-1px);
            box-shadow: 0 6px 12px -2px rgba(0, 140, 69, 0.2);
        }

        .loading-spinner {
            border: 2px solid #f3f3f3;
            border-top: 2px solid #008C45;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .footer {
            background: white;
            border-top: 1px solid #e5e7eb;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 2px;
            background: linear-gradient(70deg, #008C45, #FFA500);
        }

        .icon-container {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: rgba(0, 140, 69, 0.1);
            color: #008C45;
        }

        .pulse-effect {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .slide-in {
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    

        * {
            font-family: 'Inter', sans-serif;
        }

        .main-container {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            max-width: 1800px !important;
            padding: 1rem !important;
        }

        /* Estilos para Cards (Mobile) */
        .class-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
            min-height: 350px;
            max-height: 500px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            margin: 0;
        }

        .class-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .student-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 8px 12px;
            margin-bottom: 6px;
            transition: all 0.2s ease;
        }

        .student-card:hover {
            background: #f3f4f6;
        }

        /* Estilos para Tabelas (Desktop) */
        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .table-header {
            background: linear-gradient(90deg, #008C45, #3CB371);
            color: white;
            padding: 12px 16px;
        }

        .table-content {
            flex: 1;
            overflow-y: auto;
            max-height: 600px;
        }

        .table-row {
            border-bottom: 1px solid #f3f4f6;
            transition: background-color 0.2s ease;
        }

        .table-row:hover {
            background-color: #f9fafb;
        }

        .table-row:last-child {
            border-bottom: none;
        }

        /* Classes específicas para cada turma */
        .turma-3a .table-header {
            background: linear-gradient(90deg, #dc3545, #c82333);
        }

        .turma-3b .table-header {
            background: linear-gradient(90deg, #4169E1, #3651d1);
        }

        .turma-3c .table-header {
            background: linear-gradient(90deg, #0dcaf0, #0bb5d6);
        }

        .turma-3d .table-header {
            background: linear-gradient(90deg, #6c757d, #5a6268);
        }

        .card-header-3a {
            background: linear-gradient(90deg, #dc3545, #c82333);
        }

        .card-header-3b {
            background: linear-gradient(90deg, #4169E1, #3651d1);
        }

        .card-header-3c {
            background: linear-gradient(90deg, #0dcaf0, #0bb5d6);
        }

        .card-header-3d {
            background: linear-gradient(90deg, #6c757d, #5a6268);
        }

        .gradient-text {
            background: linear-gradient(45deg, #008C45, #3CB371);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Responsividade */
        .desktop-view {
            display: none !important;
        }

        .mobile-view {
            display: block !important;
        }

        /* Layout Grid */
        .mobile-view .space-y-6 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            padding: 1rem;
            max-width: 1800px;
            margin: 0 auto;
        }

        @media (max-width: 1400px) {
            .mobile-view .space-y-6 {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .mobile-view .space-y-6 {
                grid-template-columns: 1fr;
                padding: 0.5rem;
            }

            .student-card {
                background: white;
                border-radius: 12px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                margin-bottom: 1rem;
                padding: 1rem;
                border: 1px solid #e5e7eb;
            }

            .student-card .flex {
                flex-direction: column;
                gap: 0.5rem;
            }

            .student-card .flex-shrink-0 {
                margin-bottom: 0.5rem;
            }

            .student-card .text-sm {
                margin-top: 0.5rem;
                padding-top: 0.5rem;
                border-top: 1px solid #e5e7eb;
            }

            .class-card {
                min-height: auto;
                max-height: none;
                box-shadow: none;
                border: none;
                background: transparent;
            }

            .class-card .card-header-3a,
            .class-card .card-header-3b,
            .class-card .card-header-3c,
            .class-card .card-header-3d {
                position: sticky;
                top: 0;
                z-index: 10;
                border-radius: 12px;
                margin-bottom: 1rem;
            }

            .class-card .compact-cards {
                padding: 0;
            }

            .search-input {
                max-width: 100%;
                margin-top: 0.5rem;
            }
        }

        .class-card {
            height: 100%;
            display: flex;
            flex-direction: column;
            min-height: 350px;
            max-height: 500px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            margin: 0;
        }

        .class-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .class-card .compact-cards {
            flex: 1;
            overflow-y: auto;
            padding: 0.75rem;
        }

        .student-card {
            margin-bottom: 0.5rem;
            padding: 0.75rem;
            border-radius: 0.5rem;
            background: #f8fafc;
            transition: background-color 0.2s ease;
        }

        .student-card:hover {
            background: #f1f5f9;
        }

        /* Ajustes para o cabeçalho dos cards */
        .card-header-3a,
        .card-header-3b,
        .card-header-3c,
        .card-header-3d {
            padding: 1rem;
        }

        .card-header-3a h2,
        .card-header-3b h2,
        .card-header-3c h2,
        .card-header-3d h2 {
            font-size: 1.1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Scrollbar personalizada */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Otimizações para muitos alunos */
        .compact-table td {
            padding-top: 6px;
            padding-bottom: 6px;
            font-size: 0.875rem;
        }

        .compact-table th {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #f9fafb;
        }

        .compact-cards {
            max-height: 400px;
            overflow-y: auto;
        }

        .compact-card {
            padding: 8px 12px;
            margin-bottom: 4px;
        }

        /* Filtro de busca */
        .search-input {
            background-color: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            border-radius: 6px;
            padding: 4px 12px;
            width: 100%;
            max-width: 200px;
            font-size: 0.875rem;
        }

        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .search-input:focus {
            outline: none;
            background-color: rgba(255, 255, 255, 0.3);
        }

        /* Paginação */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 4px;
            margin-top: 8px;
            padding: 8px;
            border-top: 1px solid #f3f4f6;
        }

        .pagination-btn {
            padding: 4px 8px;
            border-radius: 4px;
            background: #f3f4f6;
            color: #374151;
            font-size: 0.75rem;
            cursor: pointer;
        }

        .pagination-btn.active {
            background: #008C45;
            color: white;
        }

        /* Footer geométrico */
        .geometric-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100px;
            z-index: -1;
            overflow: hidden;
        }

        .geometric-shape {
            position: absolute;
            bottom: 0;
        }

        .shape-1 {
            left: 0;
            width: 0;
            height: 0;
            border-left: 200px solid #FFA500;
            border-top: 100px solid transparent;
        }

        .shape-2 {
            left: 150px;
            width: 0;
            height: 0;
            border-left: 250px solid #8CA03E;
            border-top: 80px solid transparent;
            opacity: 0.9;
        }

        .shape-3 {
            left: 350px;
            width: 0;
            height: 0;
            border-left: 300px solid #008C45;
            border-top: 60px solid transparent;
            opacity: 0.8;
        }

        .shape-4 {
            right: 0;
            width: 0;
            height: 0;
            border-right: 200px solid #FFA500;
            border-top: 100px solid transparent;
            opacity: 0.7;
        }

        .shape-5 {
            right: 150px;
            width: 0;
            height: 0;
            border-right: 250px solid #8CA03E;
            border-top: 80px solid transparent;
            opacity: 0.6;
        }

        .shape-6 {
            right: 350px;
            width: 0;
            height: 0;
            border-right: 300px solid #008C45;
            border-top: 60px solid transparent;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .shape-1 {
                border-left-width: 120px;
            }

            .shape-2 {
                border-left-width: 150px;
                left: 100px;
            }

            .shape-3 {
                border-left-width: 180px;
                left: 200px;
            }

            .shape-4 {
                border-right-width: 120px;
            }

            .shape-5 {
                border-right-width: 150px;
                right: 100px;
            }

            .shape-6 {
                border-right-width: 180px;
                right: 200px;
            }
        }
    

        * {
            font-family: 'Inter', sans-serif;
        }

        .header {
            background: linear-gradient(90deg, #008C45, #3CB371);
        }

        .gradient-text {
            background: linear-gradient(45deg, #008C45, #3CB371);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }

        .input-focus-ring:focus {
            box-shadow: 0 0 0 3px rgba(0, 140, 69, 0.1);
        }

        .floating-label {
            transition: all 0.3s ease;
            pointer-events: none;
            background: white;
            padding: 0 4px;
            z-index: 10;
        }

        .input-group:focus-within .floating-label,
        .input-group.has-value .floating-label {
            transform: translateY(-1.8rem) scale(0.85);
            color: #008C45;
            font-weight: 500;
        }

        .input-field {
            position: relative;
            z-index: 5;
        }

        .shake {
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .slide-in {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .pulse-success {
            animation: pulseSuccess 0.6s ease-in-out;
        }

        @keyframes pulseSuccess {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }

        .loading-spinner {
            border: 2px solid #f3f3f3;
            border-top: 2px solid #008C45;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .btn-primary {
            background: linear-gradient(90deg, #008C45, #3CB371);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, #006d35, #2e8b57);
            transform: translateY(-1px);
            box-shadow: 0 6px 12px -2px rgba(0, 140, 69, 0.2);
        }

        .footer {
            background: white;
            border-top: 1px solid #e5e7eb;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 2px;
            background: linear-gradient(70deg, #008C45, #FFA500);
        }

        .icon-container {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: rgba(0, 140, 69, 0.1);
            color: #008C45;
        }
    
</style>
</head>
<body><aside class="sidebar" aria-label="Navegação principal">
  <button class="sidebar-close" type="button" aria-label="Fechar menu"><i class="fas fa-times" aria-hidden="true"></i></button>
  <a class="brand" href="#" data-section-target="inicio"><span class="brand-mark">S</span><span>Sistema Salaberga<small>Entradas e saídas escolares</small></span></a>
    <nav>
        <details class="nav-group" data-sidebar-group="registros"><summary class="nav-label">Registros</summary><button class="nav-item" type="button" data-target="entrada"><i class="fas fa-arrow-right-to-bracket"></i>Manual de entrada</button><button class="nav-item" type="button" data-target="saida"><i class="fas fa-arrow-right-from-bracket"></i>Manual de saída</button><button class="nav-item" type="button" data-target="estagio"><i class="fas fa-briefcase"></i>Saída para estágio</button><button class="nav-item" type="button" data-target="cadastro"><i class="fas fa-user-plus"></i>Cadastrar aluno</button></details>
        <details class="nav-group" data-sidebar-group="consultas"><summary class="nav-label">Consultas</summary><button class="nav-item" type="button" data-target="relatorio-saida"><i class="fas fa-arrow-right-from-bracket"></i>Saída antecipada</button><button class="nav-item" type="button" data-target="relatorio-estagio"><i class="fas fa-user-tie"></i>Saídas de estágio</button><button class="nav-item" type="button" data-target="atrasos"><i class="fas fa-clock-rotate-left"></i>Atrasos registrados</button><button class="nav-item" type="button" data-target="ultimas-saidas"><i class="fas fa-clock"></i>Últimas Saídas</button></details>
        <details class="nav-group" data-sidebar-group="relatorios"><summary class="nav-label">Relatórios</summary><button class="nav-item report-nav" type="button" data-target="relatorio-dia"><i class="fas fa-calendar-day"></i>Atrasos registrados</button><button class="nav-item report-nav" type="button" data-target="relatorio-saida"><i class="fas fa-arrow-right-from-bracket"></i>Saídas antecipadas</button><button class="nav-item report-nav" type="button" data-target="relatorio-estagio"><i class="fas fa-user-tie"></i>Saídas de estágio</button><a class="nav-item report-nav" href="relatorios/pre_estagio.php" target="_blank" rel="noopener"><i class="fas fa-graduation-cap"></i>Preparação para estágio</a><button class="nav-item report-nav" type="button" data-target="qrcode"><i class="fas fa-qrcode"></i>QR Code</button></details>
  </nav>
  <div class="sidebar-bottom"><a class="nav-item logout" href="../model/sessions.php?sair"><i class="fas fa-right-from-bracket"></i>Sair</a></div>
</aside>
<button class="sidebar-scrim" type="button" aria-label="Fechar menu"></button>
<button class="mobile-menu" type="button" aria-label="Abrir menu" aria-expanded="false"><i class="fas fa-bars"></i></button>
<main class="main-content"><section class="page-section" id="inicio" aria-label="Visão geral">

  

  <div class="flex-1 container mx-auto px-4 py-8 mt-16">
    <div class="max-w-4xl mx-auto">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold mb-2">
          <span class="gradient-text">Sistema de Entradas e Saídas</span>
        </h1>
        <p class="text-gray-600">Gerencie as entradas e saídas dos alunos de forma eficiente</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <a href="#" data-section-target="entrada" class="menu-card p-6 flex items-center gap-4 group">
          <div class="menu-icon bg-blue-50 text-blue-600">
            <i class="fas fa-sign-in-alt text-xl"></i>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">Registrar Entrada</h3>
            <p class="text-sm text-gray-500">Registre a entrada dos alunos</p>
          </div>
        </a>

        <a href="#" data-section-target="saida" class="menu-card p-6 flex items-center gap-4 group">
          <div class="menu-icon bg-red-50 text-red-600">
            <i class="fas fa-sign-out-alt text-xl"></i>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900 group-hover:text-red-600 transition-colors">Registrar Saída</h3>
            <p class="text-sm text-gray-500">Registre a saída dos alunos</p>
          </div>
        </a>

        <a href="#" data-section-target="estagio" class="menu-card p-6 flex items-center gap-4 group">
          <div class="menu-icon bg-purple-50 text-purple-600">
            <i class="fas fa-briefcase text-xl"></i>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900 group-hover:text-purple-600 transition-colors">Registrar Saída-Estágio</h3>
            <p class="text-sm text-gray-500">Registre saídas para estágio</p>
          </div>
        </a>

        <a href="#" data-section-target="relatorios" class="menu-card p-6 flex items-center gap-4 group">
          <div class="menu-icon bg-yellow-50 text-yellow-600">
            <i class="fas fa-chart-bar text-xl"></i>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900 group-hover:text-yellow-600 transition-colors">Relatórios</h3>
            <p class="text-sm text-gray-500">Visualize relatórios do sistema</p>
          </div>
        </a>

        <a href="#" data-section-target="ultimas-saidas" class="menu-card p-6 flex items-center gap-4 group">
          <div class="menu-icon bg-cyan-50 text-cyan-600">
            <i class="fas fa-history text-xl"></i>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900 group-hover:text-cyan-600 transition-colors">Últimas Saídas</h3>
            <p class="text-sm text-gray-500">Acompanhe as últimas saídas registradas</p>
          </div>
        </a>
      </div>
    </div>
  </div>

  

</section>

<section class="page-section active" id="entrada" aria-label="Registrar Entrada">

    
    

    <!-- Main Container -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Form Header -->
            <div class="bg-gradient-to-r from-ceara-green to-ceara-light-green text-white px-6 py-8 text-center">
                <h2 class="text-2xl font-bold mb-2">
                    <i class="fas fa-sign-in-alt mr-3"></i>
                    Registrar Entrada de Aluno
                </h2>
                <p class="text-lg opacity-90">
                    Preencha os dados para registrar a entrada do aluno
                </p>
            </div>

            <!-- Form Content -->
            <div class="p-6 lg:p-8">
                <form id="registro-e" action="../control/control_index.php" method="POST" class="space-y-6">

                    <!-- Seção: Dados do Aluno -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-user-graduate text-ceara-green mr-3"></i>
                            Dados do Aluno
                        </h3>
                        <div class="form-group">
                            <label for="entrada-id_aluno" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                Nome do Aluno
                            </label>
                            <div class="relative">
                                <div class="custom-select-container">
                                    <div class="select-trigger" id="entrada-select-trigger">
                                        <span class="select-placeholder">Selecione o Nome do Aluno</span>
                                        <i class="fas fa-chevron-down select-arrow"></i>
                                    </div>
                                    <div class="select-dropdown" id="entrada-select-dropdown">
                                        <div class="search-container">
                                            <input type="text" id="entrada-search_aluno" placeholder="Digite para pesquisar..." class="search-input">
                                            <i class="fas fa-search search-icon"></i>
                                        </div>
                                        <div class="options-container" id="entrada-options-container">
                                            <?php
                                            $dados = $select->select_alunos();
                                            foreach ($dados as $dado) {
                                            ?>
                                                <div class="select-option" data-value="<?= $dado['id_aluno'] ?>" data-nome="<?= strtolower($dado['nome']) ?>">
                                                    <?= $dado['nome'] ?>
                                                </div>
                                            <?php
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <select id="entrada-id_aluno" name="id_aluno" class="hidden-select" required>
                                        <option value="" disabled selected>Selecione o Nome do Aluno</option>
                                        <?php
                                        foreach ($dados as $dado) {
                                        ?>
                                            <option value="<?= $dado['id_aluno'] ?>"><?= $dado['nome'] ?></option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Seção: Dados do Responsável -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-user-shield text-info mr-3"></i>
                            Dados do Responsável
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label for="entrada-nome_responsavel" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Nome do Responsável
                                </label>
                                <input type="text" id="entrada-nome_responsavel" name="nome_responsavel"
                                    placeholder="Digite o nome completo"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green" required>
                            </div>
                            <div class="form-group">
                                <label for="entrada-id_tipo_responsavel" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Tipo de Responsável
                                </label>
                                <select id="entrada-id_tipo_responsavel" name="id_tipo_responsavel" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green form-select" required>
                                    <option value="" disabled selected>Selecione o tipo</option>
                                    <?php
                                    $dados = $select->select_responsavel();
                                    foreach ($dados as $dado) {
                                    ?>

                                        <option value="<?=$dado['id_tipo_responsavel']?>"><?=$dado['tipo']?></option>
                                    <?php } ?>
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Seção: Dados do Conducente -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-car text-secondary mr-3"></i>
                            Dados do Acompanhante
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label for="entrada-nome_conducente" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nome do Acompanhante
                                </label>
                                <input type="text" id="entrada-nome_conducente" name="nome_conducente"
                                    placeholder="Digite o nome do acompanhante"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green">
                            </div>
                            <div class="form-group">
                                <label for="entrada-id_tipo_conducente" class="block text-sm font-medium text-gray-700 mb-2">
                                    Tipo de Acompanhante
                                </label>
                                <select id="entrada-id_tipo_conducente" name="id_tipo_conducente" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green form-select">
                                    <option value="" disabled selected>Selecione o tipo</option>
                                    <?php
                                    $dados = $select->select_conducente();
                                    foreach ($dados as $dado) {
                                    ?>

                                        <option value="<?=$dado['id_tipo_conducente']?>"><?=$dado['tipo']?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Seção: Dados da Entrada -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-calendar-alt text-danger mr-3"></i>
                            Dados da Entrada
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div class="form-group">
                                <label for="entrada-id_motivo" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Motivo da Entrada
                                </label>
                                <select id="entrada-id_motivo" name="id_motivo" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green form-select" required>
                                    <option value="" disabled selected>Selecione o motivo</option>
                                    <?php
                                    $dados = $select->select_motivo();
                                    foreach ($dados as $dado) {
                                    ?>

                                        <option value="<?=$dado['id_motivo']?>"><?=$dado['motivo']?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="entrada-id_usuario" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Administrador
                                </label>
                                <select id="entrada-id_usuario" name="id_usuario" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green form-select" required>
                                    <option value="" disabled selected>Selecione o administrador</option>
                                    <?php
                                    $dados = $select->select_funcionario();
                                    foreach ($dados as $dado) {
                                    ?>

                                        <option value="<?=$dado['id_funcionario']?>"><?=$dado['nome']?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label for="data" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Data
                                </label>
                                <input type="date" name="data" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green" required>
                            </div>
                            <div class="form-group">
                                <label for="hora" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Horário
                                </label>
                                <input type="time" name="hora" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green" required>
                            </div>
                        </div>
                    </div>

                    <!-- Botão de Envio -->
                    <button type="submit" name="entrada" class="w-full bg-gradient-to-r from-ceara-green to-ceara-light-green text-white font-semibold py-4 px-6 rounded-lg hover:from-ceara-light-green hover:to-ceara-green transition-all duration-300 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-ceara-green focus:ring-opacity-50">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Registrar Entrada
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    

    

</section>

<script>
(() => {
$(document).ready(function() {
            $('#entrada .js-example-basic-single').select2({
                placeholder: 'Selecione o aluno',
                allowClear: true,
                dropdownParent: $('#entrada'),
                width: '100%',
                language: 'pt-BR',
                minimumResultsForSearch: 0
            });
        });
        // Custom Select Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const selectTrigger = document.getElementById('entrada-select-trigger');
            const selectDropdown = document.getElementById('entrada-select-dropdown');
            const searchInput = document.getElementById('entrada-search_aluno');
            const optionsContainer = document.getElementById('entrada-options-container');
            const selectOptions = optionsContainer.querySelectorAll('.select-option');
            const hiddenSelect = document.getElementById('entrada-id_aluno');
            const placeholder = selectTrigger.querySelector('.select-placeholder');

            // Toggle dropdown
            selectTrigger.addEventListener('click', function(e) {
                e.stopPropagation();
                selectDropdown.classList.toggle('active');
                selectTrigger.classList.toggle('active');

                if (selectDropdown.classList.contains('active')) {
                    searchInput.focus();
                }
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!selectTrigger.contains(e.target) && !selectDropdown.contains(e.target)) {
                    selectDropdown.classList.remove('active');
                    selectTrigger.classList.remove('active');
                }
            });

            // Search functionality
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();

                selectOptions.forEach(option => {
                    const nome = option.getAttribute('data-nome');
                    if (nome.includes(searchTerm)) {
                        option.classList.remove('hidden');
                    } else {
                        option.classList.add('hidden');
                    }
                });
            });

            // Option selection
            selectOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const value = this.getAttribute('data-value');
                    const text = this.textContent;

                    // Update hidden select
                    hiddenSelect.value = value;

                    // Update trigger display
                    placeholder.textContent = text;
                    placeholder.style.color = '#374151';

                    // Update visual state
                    selectOptions.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');

                    // Close dropdown
                    selectDropdown.classList.remove('active');
                    selectTrigger.classList.remove('active');

                    // Clear search
                    searchInput.value = '';
                    selectOptions.forEach(opt => opt.classList.remove('hidden'));
                });
            });

            // Keyboard navigation
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    selectDropdown.classList.remove('active');
                    selectTrigger.classList.remove('active');
                }
            });

            // Auto-focus on search when dropdown opens
            selectTrigger.addEventListener('click', function() {
                setTimeout(() => {
                    searchInput.focus();
                }, 100);
            });
        });

        // Validação em tempo real
        const form = document.getElementById('registro-e');
        const inputs = form.querySelectorAll('input, select');

        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.hasAttribute('required') && !this.value) {
                    this.classList.add('border-red-500');
                    this.classList.remove('border-gray-300');
                } else {
                    this.classList.remove('border-red-500');
                    this.classList.add('border-gray-300');
                }
            });

            input.addEventListener('input', function() {
                if (this.value) {
                    this.classList.remove('border-red-500');
                    this.classList.add('border-gray-300');
                }
            });
        });

        // Prevenção de envio duplo
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Registrando...';
        });
})();
</script>

<section class="page-section" id="saida" aria-label="Registrar Saída">

    
    

    <!-- Main Container -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Form Header -->
            <div class="bg-gradient-to-r from-ceara-green to-ceara-light-green text-white px-6 py-8 text-center">
                <h2 class="text-2xl font-bold mb-2">
                    <i class="fas fa-sign-out-alt mr-3"></i>
                    Registrar Saída de Aluno
                </h2>
                <p class="text-lg opacity-90">
                    Preencha os dados para registrar a saída do aluno
                </p>
            </div>

            <!-- Form Content -->
            <div class="p-6 lg:p-8">
                <form id="registro-s" action="../control/control_index.php" method="POST" class="space-y-6">
                    
                    <!-- Seção: Dados do Aluno -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-user-graduate text-ceara-green mr-3"></i>
                            Dados do Aluno
                        </h3>
                        <div class="form-group">
                            <label for="saida-id_aluno" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                Nome do Aluno
                            </label>
                            <div class="relative">
                                <div class="custom-select-container">
                                    <div class="select-trigger" id="saida-select-trigger">
                                        <span class="select-placeholder">Selecione o Nome do Aluno</span>
                                        <i class="fas fa-chevron-down select-arrow"></i>
                                    </div>
                                    <div class="select-dropdown" id="saida-select-dropdown">
                                        <div class="search-container">
                                            <input type="text" id="saida-search_aluno" placeholder="Digite para pesquisar..." class="search-input">
                                            <i class="fas fa-search search-icon"></i>
                                        </div>
                                        <div class="options-container" id="saida-options-container">
                                            <?php
                                            $dados = $select->select_alunos();
                                            foreach ($dados as $dado) {
                                            ?>
                                            <div class="select-option" data-value="<?=$dado['id_aluno']?>" data-nome="<?=strtolower($dado['nome'])?>">
                                                <?=$dado['nome']?>
                                            </div>
                                            <?php
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <select id="saida-id_aluno" name="id_aluno" class="hidden-select" required>
                                        <option value="" disabled selected>Selecione o Nome do Aluno</option>
                                        <?php
                                        foreach ($dados as $dado) {
                                        ?>
                                        <option value="<?=$dado['id_aluno']?>"><?=$dado['nome']?></option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Seção: Dados do Responsável -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-user-shield text-info mr-3"></i>
                            Dados do Responsável
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label for="saida-nome_responsavel" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Nome do Responsável
                                </label>
                                <input type="text" id="saida-nome_responsavel" name="nome_responsavel" 
                                       placeholder="Digite o nome completo" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green" required>
                            </div>
                            <div class="form-group">
                                <label for="saida-id_tipo_responsavel" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Tipo de Responsável
                                </label>
                                <select id="saida-id_tipo_responsavel" name="id_tipo_responsavel" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green form-select" required>
                                    <option value="" disabled selected>Selecione o tipo</option>
                                    <?php
                                    $dados = $select->select_responsavel();
                                    foreach ($dados as $dado) {
                                    ?>

                                        <option value="<?=$dado['id_tipo_responsavel']?>"><?=$dado['tipo']?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Seção: Dados do Conducente -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-car text-secondary mr-3"></i>
                            Dados do Conducente
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label for="saida-nome_conducente" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nome do Conducente
                                </label>
                                <input type="text" id="saida-nome_conducente" name="nome_conducente" 
                                       placeholder="Digite o nome do conducente" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green">
                            </div>
                            <div class="form-group">
                                <label for="saida-id_tipo_conducente" class="block text-sm font-medium text-gray-700 mb-2">
                                    Tipo de Conducente
                                </label>
                                <select id="saida-id_tipo_conducente" name="id_tipo_conducente" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green form-select">
                                    <option value="" disabled selected>Selecione o tipo</option>
                                    <?php
                                    $dados = $select->select_conducente();
                                    foreach ($dados as $dado) {
                                    ?>

                                        <option value="<?=$dado['id_tipo_conducente']?>"><?=$dado['tipo']?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Seção: Dados da Saída -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-calendar-alt text-danger mr-3"></i>
                            Dados da Saída
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div class="form-group">
                                <label for="saida-id_motivo" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Motivo da Saída
                                </label>
                                <select id="saida-id_motivo" name="id_motivo" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green form-select" required>
                                    <option value="" disabled selected>Selecione o motivo</option>
                                    <?php
                                    $dados = $select->select_motivo();
                                    foreach ($dados as $dado) {
                                    ?>

                                        <option value="<?=$dado['id_motivo']?>"><?=$dado['motivo']?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="saida-id_usuario" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Administrador
                                </label>
                                <select id="saida-id_usuario" name="id_usuario" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green form-select" required>
                                    <option value="" disabled selected>Selecione o administrador</option>
                                    <?php
                                    $dados = $select->select_funcionario();
                                    foreach ($dados as $dado) {
                                    ?>

                                        <option value="<?=$dado['id_funcionario']?>"><?=$dado['nome']?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label for="data" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Data
                                </label>
                                <input type="date" name="data" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green" required>
                            </div>
                            <div class="form-group">
                                <label for="hora" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Horário
                                </label>
                                <input type="time" name="hora" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green" required>
                            </div>
                        </div>
                    </div>

                    <!-- Botão de Envio -->
                    <button type="submit" name="saida" class="w-full bg-gradient-to-r from-ceara-green to-ceara-light-green text-white font-semibold py-4 px-6 rounded-lg hover:from-ceara-light-green hover:to-ceara-green transition-all duration-300 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-ceara-green focus:ring-opacity-50">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Registrar Saída
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    

    

</section>

<script>
(() => {
// Custom Select Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const selectTrigger = document.getElementById('saida-select-trigger');
            const selectDropdown = document.getElementById('saida-select-dropdown');
            const searchInput = document.getElementById('saida-search_aluno');
            const optionsContainer = document.getElementById('saida-options-container');
            const selectOptions = optionsContainer.querySelectorAll('.select-option');
            const hiddenSelect = document.getElementById('saida-id_aluno');
            const placeholder = selectTrigger.querySelector('.select-placeholder');

            // Toggle dropdown
            selectTrigger.addEventListener('click', function(e) {
                e.stopPropagation();
                selectDropdown.classList.toggle('active');
                selectTrigger.classList.toggle('active');
                
                if (selectDropdown.classList.contains('active')) {
                    searchInput.focus();
                }
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!selectTrigger.contains(e.target) && !selectDropdown.contains(e.target)) {
                    selectDropdown.classList.remove('active');
                    selectTrigger.classList.remove('active');
                }
            });

            // Search functionality
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                
                selectOptions.forEach(option => {
                    const nome = option.getAttribute('data-nome');
                    if (nome.includes(searchTerm)) {
                        option.classList.remove('hidden');
                    } else {
                        option.classList.add('hidden');
                    }
                });
            });

            // Option selection
            selectOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const value = this.getAttribute('data-value');
                    const text = this.textContent;
                    
                    // Update hidden select
                    hiddenSelect.value = value;
                    
                    // Update trigger display
                    placeholder.textContent = text;
                    placeholder.style.color = '#374151';
                    
                    // Update visual state
                    selectOptions.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                    
                    // Close dropdown
                    selectDropdown.classList.remove('active');
                    selectTrigger.classList.remove('active');
                    
                    // Clear search
                    searchInput.value = '';
                    selectOptions.forEach(opt => opt.classList.remove('hidden'));
                });
            });

            // Keyboard navigation
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    selectDropdown.classList.remove('active');
                    selectTrigger.classList.remove('active');
                }
            });

            // Auto-focus on search when dropdown opens
            selectTrigger.addEventListener('click', function() {
                setTimeout(() => {
                    searchInput.focus();
                }, 100);
            });
        });

        // Validação em tempo real
        const form = document.getElementById('registro-s');
        const inputs = form.querySelectorAll('input, select');

        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.hasAttribute('required') && !this.value) {
                    this.classList.add('border-red-500');
                    this.classList.remove('border-gray-300');
                } else {
                    this.classList.remove('border-red-500');
                    this.classList.add('border-gray-300');
                }
            });

            input.addEventListener('input', function() {
                if (this.value) {
                    this.classList.remove('border-red-500');
                    this.classList.add('border-gray-300');
                }
            });
        });

        // Prevenção de envio duplo
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Registrando...';
        });
})();
</script>

<section class="page-section" id="estagio" aria-label="Registrar Saída-Estágio">

    
    

    <!-- Main Content -->
    <div class="flex-1 container mx-auto px-4 py-8 mt-16">
        <div class="max-w-lg mx-auto slide-in">
            <!-- Title Section -->
            <div class="text-center mb-8">
                <div class="icon-container mx-auto mb-4">
                    <i class="fas fa-briefcase text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold mb-2">
                    <span class="gradient-text">Registro de Saída Estágio</span>
                </h1>
                <p class="text-gray-600 text-sm">Registre a saída dos alunos para estágio</p>
            </div>

            <!-- Success Message -->
            <div id="estagio-successMessage" class="hidden mb-6 p-4 bg-green-50 border border-green-200 rounded-lg success-message">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 mr-3"></i>
                    <span class="text-green-700 font-medium">Saída registrada com sucesso!</span>
                </div>
            </div>

            <!-- Form Container -->
            <div class="form-card p-8 border-t-4 border-ceara-green">
                <form id="saida-estagio" action="../control/control_index.php" method="POST" class="space-y-6">
                    
                    <!-- Aluno Selection -->
                    <div class="space-y-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-ceara-green text-sm"></i>
                            </div>
                            <label for="estagio-id_aluno" class="text-base font-semibold text-gray-800">
                                Aluno
                            </label>
                        </div>
                        
                        <div class="relative">
                            <select 
                                class="js-example-basic-single w-full p-4 border-2 border-gray-200 rounded-lg focus:border-ceara-green input-focus-ring outline-none transition-all duration-300 text-base bg-white hover:border-gray-300"
                                name="id_aluno" 
                                required
                                id="estagio-id_aluno"
                            >
                                <option value="" disabled selected>Selecione o aluno</option>
                                <?php
                                $dados = $select->select_alunosE();
                                foreach ($dados as $dado) {
                                ?>
                                    <option value="<?=$dado['id_aluno']?>"><?=$dado['nome']?></option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div id="aluno-error" class="hidden text-red-500 text-sm flex items-center mt-2">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <span>Por favor, selecione um aluno</span>
                        </div>
                    </div>

                    <!-- Data e Hora Section -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-medium text-gray-700 flex items-center">
                            <i class="fas fa-calendar-alt mr-2 text-ceara-green"></i>
                            Data e Hora da Saída
                        </h3>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Data -->
                            <div class="space-y-2">
                                <label for="estagio-data" class="block text-xs font-medium text-gray-600">
                                    Data
                                </label>
                                <input 
                                    type="date" 
                                    id="estagio-data"
                                    name="data" 
                                    class="w-full p-3 border-2 border-gray-200 rounded-lg focus:border-ceara-green input-focus-ring outline-none transition-all duration-300 text-sm"
                                    required
                                >
                                <div id="data-error" class="hidden text-red-500 text-xs flex items-center mt-1">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    <span>Data é obrigatória</span>
                                </div>
                            </div>

                            <!-- Hora -->
                            <div class="space-y-2">
                                <label for="hora" class="block text-xs font-medium text-gray-600">
                                    Hora
                                </label>
                                <input 
                                    type="time" 
                                    id="hora"
                                    name="hora" 
                                    class="w-full p-3 border-2 border-gray-200 rounded-lg focus:border-ceara-green input-focus-ring outline-none transition-all duration-300 text-sm"
                                    required
                                >
                                <div id="hora-error" class="hidden text-red-500 text-xs flex items-center mt-1">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    <span>Hora é obrigatória</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        id="estagio-submitBtn"
                        name="Registrar"
                        class="w-full btn-primary text-white font-medium py-4 px-6 rounded-lg focus:outline-none focus:ring-4 focus:ring-green-200 disabled:opacity-50 disabled:cursor-not-allowed text-sm"
                    >
                        <span id="estagio-submitText" class="flex items-center justify-center gap-2">
                            <i class="fas fa-save"></i>
                            Registrar Saída
                        </span>
        
                    </button>
                </form>

                <!-- Back Links -->
                <div class="flex flex-col sm:flex-row gap-3 mt-6 text-center">
                    <a href="#" data-section-target="inicio" class="flex-1 inline-flex items-center justify-center gap-2 text-ceara-green hover:text-ceara-light-green font-medium transition-colors duration-300 text-sm">
                        <i class="fas fa-arrow-left text-sm"></i>
                        Voltar ao Menu
                    </a>
                    <a href="#" data-section-target="ultimas-saidas" class="flex-1 inline-flex items-center justify-center gap-2 text-blue-600 hover:text-blue-700 font-medium transition-colors duration-300 text-sm">
                        <i class="fas fa-eye text-sm"></i>
                        Ver em Tempo Real
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    

    <!-- Modal de Sucesso -->
    <div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 transform transition-all duration-300 scale-95 opacity-0" id="successModalContent">
            <div class="text-center">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-4">Sucesso!</h3>
                <p class="text-gray-600 mb-8">A saída do aluno foi registrada com sucesso.</p>
                <button type="button" data-close-modal="success" class="w-full bg-green-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-green-700 transition-colors">
                    Continuar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Erro -->
    <div id="errorModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 transform transition-all duration-300 scale-95 opacity-0" id="errorModalContent">
            <div class="text-center">
                <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-4">Erro!</h3>
                <p id="errorMessage" class="text-gray-600 mb-8">Ocorreu um erro ao registrar a saída.</p>
                <button type="button" data-close-modal="error" class="w-full bg-red-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-red-700 transition-colors">
                    Tentar Novamente
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Aviso -->
    <div id="warningModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 transform transition-all duration-300 scale-95 opacity-0" id="warningModalContent">
            <div class="text-center">
                <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-4">Aviso!</h3>
                <p class="text-gray-600 mb-8">Este aluno já possui um registro de saída para hoje.</p>
                <button type="button" data-close-modal="warning" class="w-full bg-yellow-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-yellow-700 transition-colors">
                    Entendi
                </button>
            </div>
        </div>
    </div>

    

</section>

<script>
$(document).ready(function() {
            $('#estagio .js-example-basic-single').select2({
                placeholder: 'Selecione o aluno',
                allowClear: true,
                dropdownParent: $('#estagio'),
                width: '100%',
                language: 'pt-BR',
                minimumResultsForSearch: 0
            });
        });

        class SaidaEstagioForm {
            constructor() {
                this.form = document.getElementById('saida-estagio');
                this.fields = {
                    aluno: document.getElementById('estagio-id_aluno'),
                    data: document.getElementById('estagio-data'),
                    hora: document.getElementById('hora')
                };
                this.submitBtn = document.getElementById('estagio-submitBtn');
                this.init();
            }

            init() {
                // Set current date and time as default
                this.setCurrentDateTime();

                // Add event listeners for validation
                Object.keys(this.fields).forEach(fieldName => {
                    const field = this.fields[fieldName];
                    field.addEventListener('change', () => this.validateField(fieldName));
                    field.addEventListener('blur', () => this.validateField(fieldName));
                });

                // Form submission
                this.form.addEventListener('submit', (e) => this.handleSubmit(e));

                // Add visual feedback on field changes
                Object.values(this.fields).forEach(field => {
                    field.addEventListener('change', () => {
                        if (field.value) {
                            field.classList.add('border-green-500');
                            field.classList.remove('border-gray-200');
                        } else {
                            field.classList.remove('border-green-500');
                            field.classList.add('border-gray-200');
                        }
                    });
                });
            }

            setCurrentDateTime() {
                const now = new Date();
                const today = now.toISOString().split('T')[0];
                const currentTime = now.toTimeString().slice(0, 5);
                
                this.fields.data.value = today;
                this.fields.hora.value = currentTime;
                
                // Trigger change events to update visual feedback
                this.fields.data.dispatchEvent(new Event('change'));
                this.fields.hora.dispatchEvent(new Event('change'));
            }

            validateField(fieldName) {
                const field = this.fields[fieldName];
                const errorElement = document.getElementById(`${fieldName}-error`);
                
                if (!field.value) {
                    if (errorElement) {
                        errorElement.classList.remove('hidden');
                    }
                    return false;
                } else {
                    if (errorElement) {
                        errorElement.classList.add('hidden');
                    }
                    return true;
                }
            }

            validateForm() {
                let isValid = true;
                Object.keys(this.fields).forEach(fieldName => {
                    if (!this.validateField(fieldName)) {
                        isValid = false;
                    }
                });
                return isValid;
            }

            handleSubmit(e) {
                if (!this.validateForm()) {
                    e.preventDefault();
                    
                    // Show error modal for validation failures
                    showErrorModal('Por favor, preencha todos os campos obrigatórios.');
                    
                    // Shake effect for invalid form
                    const container = this.form.closest('.page-section').querySelector('.form-card');
                    container.classList.add('shake');
                    
                    setTimeout(() => {
                        container.classList.remove('shake');
                    }, 500);
                    
                    return;
                }

                // Show loading state
                this.showLoadingState();
            }

            showLoadingState() {
                const submitText = document.getElementById('estagio-submitText');
                const submitLoading = document.getElementById('submitLoading');
                
                submitText.classList.add('hidden');
                submitLoading.classList.remove('hidden');
                this.submitBtn.disabled = true;
            }

            showSuccessState() {
                // Reset button state
                const submitText = document.getElementById('estagio-submitText');
                const submitLoading = document.getElementById('submitLoading');
                submitText.classList.remove('hidden');
                submitLoading.classList.add('hidden');
                this.submitBtn.disabled = false;

                // Show success modal
                showSuccessModal();
                
                // Reset form
                this.form.reset();
                this.setCurrentDateTime();
                
                // Remove visual feedback
                Object.values(this.fields).forEach(field => {
                    field.classList.remove('border-green-500');
                    field.classList.add('border-gray-200');
                });
            }

            showErrorState(errorMessage = 'Ocorreu um erro ao registrar a saída.') {
                // Reset button state
                const submitText = document.getElementById('estagio-submitText');
                const submitLoading = document.getElementById('submitLoading');
                submitText.classList.remove('hidden');
                submitLoading.classList.add('hidden');
                this.submitBtn.disabled = false;

                // Show error modal
                showErrorModal(errorMessage);
            }
        }

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new SaidaEstagioForm();
        });

        // Handle URL parameters for success messages
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('status') === 'success') {
            // Show modal immediately if already loaded
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    setTimeout(() => showSuccessModal(), 100);
                });
            } else {
                setTimeout(() => showSuccessModal(), 100);
            }
        }
        
        if (urlParams.get('status') === 'ja_registrado') {
            // Show warning modal immediately if already loaded
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    setTimeout(() => showWarningModal(), 100);
                });
            } else {
                setTimeout(() => showWarningModal(), 100);
            }
        }

        // Modal Functions
        function showSuccessModal() {
            const modal = document.getElementById('successModal');
            const content = document.getElementById('successModalContent');
            
            if (!modal || !content) {
                console.error('Modal elements not found');
                return;
            }
            
            modal.classList.remove('hidden');
            modal.classList.add('modal-backdrop');
            
            setTimeout(() => {
                content.classList.add('modal-enter');
            }, 10);
        }

        function closeSuccessModal() {
            const modal = document.getElementById('successModal');
            const content = document.getElementById('successModalContent');
            
            content.classList.remove('modal-enter');
            content.classList.add('modal-exit');
            
            setTimeout(() => {
                modal.classList.add('hidden');
                content.classList.remove('modal-exit');
            }, 300);
        }

        function showErrorModal(message = 'Ocorreu um erro ao registrar a saída.') {
            const modal = document.getElementById('errorModal');
            const content = document.getElementById('errorModalContent');
            const errorMessage = document.getElementById('errorMessage');
            
            if (!modal || !content || !errorMessage) {
                console.error('Error modal elements not found');
                return;
            }
            
            errorMessage.textContent = message;
            modal.classList.remove('hidden');
            modal.classList.add('modal-backdrop');
            
            setTimeout(() => {
                content.classList.add('modal-enter');
            }, 10);
        }

        function closeErrorModal() {
            const modal = document.getElementById('errorModal');
            const content = document.getElementById('errorModalContent');
            
            content.classList.remove('modal-enter');
            content.classList.add('modal-exit');
            
            setTimeout(() => {
                modal.classList.add('hidden');
                content.classList.remove('modal-exit');
            }, 300);
        }

        function showWarningModal() {
            const modal = document.getElementById('warningModal');
            const content = document.getElementById('warningModalContent');
            
            if (!modal || !content) {
                console.error('Warning modal elements not found');
                return;
            }
            
            modal.classList.remove('hidden');
            modal.classList.add('modal-backdrop');
            
            setTimeout(() => {
                content.classList.add('modal-enter');
            }, 10);
        }

        function closeWarningModal() {
            const modal = document.getElementById('warningModal');
            const content = document.getElementById('warningModalContent');
            
            content.classList.remove('modal-enter');
            content.classList.add('modal-exit');
            
            setTimeout(() => {
                modal.classList.add('hidden');
                content.classList.remove('modal-exit');
            }, 300);
        }

        // Close modals when clicking outside
        document.addEventListener('click', (e) => {
            if (e.target.id === 'successModal' || e.target.id === 'errorModal' || e.target.id === 'warningModal') {
                closeSuccessModal();
                closeErrorModal();
                closeWarningModal();
            }
        });

        // Close modals with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeSuccessModal();
                closeErrorModal();
                closeWarningModal();
            }
        });

        document.querySelectorAll('[data-close-modal]').forEach(button => {
            button.addEventListener('click', () => {
                const modalType = button.dataset.closeModal;
                if (modalType === 'success') closeSuccessModal();
                if (modalType === 'error') closeErrorModal();
                if (modalType === 'warning') closeWarningModal();
            });
        });
</script>

<section class="page-section" id="relatorios" aria-label="Relatórios">

    

    <div class="flex-1 container mx-auto px-4 ">
        <div class="max-w-2xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold mb-2">
                    <span class="gradient-text">Relatórios do Sistema</span>
                </h1>
                <p class="text-gray-600">Selecione o tipo de relatório que deseja gerar</p>
            </div>

            <div class="space-y-4">
                <a href="#" data-section-target="relatorio-entrada" class="report-card block w-full p-6 text-left">
                    <div class="flex items-center gap-4">
<div class="report-icon bg-blue-50 text-blue-600">
    <i class="fas fa-right-to-bracket text-xl"></i>
</div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Relatório de Entradas</h3>
                            <p class="text-sm text-gray-500">Visualize o histórico de entradas dos alunos</p>
                        </div>
                    </div>
                </a>

                <a href="#" data-section-target="relatorio-saida" class="report-card block w-full p-6 text-left">
                    <div class="flex items-center gap-4">
<div class="report-icon bg-red-50 text-red-600">
    <i class="fas fa-right-from-bracket text-xl"></i>
</div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Relatório de Saídas</h3>
                            <p class="text-sm text-gray-500">Visualize o histórico de saídas dos alunos</p>
                        </div>
                    </div>
                </a>

                <a href="relatorios/relatorio_diario_estagio.php" target="_blank" rel="noopener" class="report-card block w-full p-6 text-left">
                    <div class="flex items-center gap-4">
<div class="report-icon bg-violet-50 text-violet-600">
    <i class="fas fa-user-tie text-xl"></i>
</div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Relatório de Saídas-Estágio</h3>
                            <p class="text-sm text-gray-500">Visualize o histórico de saídas para estágio</p>
                        </div>
                    </div>
                </a>

                <a href="#" data-section-target="relatorio-dia" class="report-card block p-6 text-left">
                    <div class="flex items-center gap-4">
<div class="report-icon bg-amber-50 text-amber-600">
    <i class="fas fa-calendar-day text-xl"></i>
</div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Relatório de Saídas-Estágio no dia especifico</h3>
                            <p class="text-sm text-gray-500">Visualize informações gerais sobre todos os alunos por dia</p>
                        </div>
                    </div>
                </a>
                
                <a href="relatorios/pre_estagio.php" target="_blank" rel="noopener" class="report-card block p-6 text-left">
                    <div class="flex items-center gap-4">
<div class="report-icon bg-emerald-50 text-emerald-600">
    <i class="fas fa-graduation-cap text-xl"></i>
</div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Preparação para estágio - Frequência</h3>
                            <p class="text-sm text-gray-500">Visualize informações gerais sobre todos os alunos por dia</p>
                        </div>
                    </div>
                </a>
                
                <a href="#" data-section-target="qrcode" class="report-card block p-6 text-left">
                    <div class="flex items-center gap-4">
<div class="report-icon bg-cyan-50 text-cyan-600">
    <i class="fas fa-qrcode text-xl"></i>
</div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Relatório Geral de QRCodes</h3>
                            <p class="text-sm text-gray-500">Gere QRCodes para todos os alunos</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    

</section>

<section class="page-section" id="relatorio-entrada" aria-label="Relatório de Entrada">

    
    

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Title Section -->
            <div class="title-section">
                <div class="icon-container">
                    <i class="fas fa-sign-in-alt"></i>
                </div>
                <h1 class="gradient-text">Relatório de Entrada</h1>
                <p class="subtitle">Gere relatórios detalhados de entradas no sistema</p>
            </div>

            <!-- Form Card -->
            <div class="form-card">
                <!-- Tabs Navigation -->
                <div class="tabs-nav">
                    <button class="tab-btn active" data-tab="aluno">
                        <i class="fas fa-user"></i> Por Aluno
                    </button>
                    <button class="tab-btn" data-tab="ano">
                        <i class="fas fa-calendar-alt"></i> Por Ano
                    </button>
                    <button class="tab-btn" data-tab="turma">
                        <i class="fas fa-users"></i> Por Turma
                    </button>
                </div>

                <!-- Tab Contents -->
                <!-- Por Aluno -->
                <div class="tab-content active" id="relatorio-entrada-tab-aluno">
                    <form target="_blank" action="../control/control_index.php" method="POST">
                        <div class="form-group">
                            <label class="form-label">Selecione o Aluno</label>
                            <select class="js-example-basic-single" name="id_aluno" required>
                                <option value="" disabled selected>Selecione o aluno</option>
                                <?php
                                $dados = $select->select_alunos();
                                foreach ($dados as $dado) {
                                ?>
                                    <option value="<?= $dado['id_aluno'] ?>"><?= $dado['nome'] ?></option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Período do Relatório</label>
                            <div class="radio-group">
                                <label class="radio-item">
                                    <input type="radio" name="tipo_relatorio" value="dia_atual" class="radio-input" required>
                                    <span class="radio-custom"></span>
                                    <span class="radio-label">Dia Atual</span>
                                </label>
                                <label class="radio-item">
                                    <input type="radio" name="tipo_relatorio" value="ultimos_30_dias" class="radio-input">
                                    <span class="radio-custom"></span>
                                    <span class="radio-label">Últimos 30 Dias</span>
                                </label>
                                <label class="radio-item">
                                    <input type="radio" name="tipo_relatorio" value="ultimos_12_meses" class="radio-input">
                                    <span class="radio-custom"></span>
                                    <span class="radio-label">Últimos 12 Meses</span>
                                </label>
                            </div>
                        </div>

                        <input type="hidden" name="GerarRelatorio" value="por_alunoEntrada">
                        <input type="hidden" name="form_id" value="entrada">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-file-export"></i>
                            Gerar Relatório
                        </button>
                    </form>
                </div>

                <!-- Por Ano -->
                <div class="tab-content" id="relatorio-entrada-tab-ano">
                    <form target="_blank" action="../control/control_index.php" method="POST">
                        <div class="form-group">
                            <label class="form-label">Selecione o Ano</label>
                            <select name="Ano" class="form-select" required>
                                <option value="" disabled selected>Selecione o ano</option>
                                <option value="1">1° Anos</option>
                                <option value="2">2° Anos</option>
                                <option value="3">3° Anos</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Período do Relatório</label>
                            <div class="radio-group">
                                <label class="radio-item">
                                    <input type="radio" name="tipo_relatorio" value="dia_atual" class="radio-input" required>
                                    <span class="radio-custom"></span>
                                    <span class="radio-label">Dia Atual</span>
                                </label>
                                <label class="radio-item">
                                    <input type="radio" name="tipo_relatorio" value="ultimos_30_dias" class="radio-input">
                                    <span class="radio-custom"></span>
                                    <span class="radio-label">Últimos 30 Dias</span>
                                </label>
                                <label class="radio-item">
                                    <input type="radio" name="tipo_relatorio" value="ultimos_12_meses" class="radio-input">
                                    <span class="radio-custom"></span>
                                    <span class="radio-label">Últimos 12 Meses</span>
                                </label>
                            </div>
                        </div>

                        <input type="hidden" name="GerarRelatorio" value="ano_geralEntrada">
                        <input type="hidden" name="form_id" value="entradaA">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-file-export"></i>
                            Gerar Relatório
                        </button>
                    </form>
                </div>

                <!-- Por Turma -->
                <div class="tab-content" id="relatorio-entrada-tab-turma">
                    <form target="_blank" action="../control/control_index.php" method="POST">
                        <div class="form-group">
                            <label class="form-label">Selecione a Turma</label>
                            <select name="Turma" class="form-select" required>
                                <option value="" disabled selected>Selecione a turma</option>
                                <option value="1">1° Ano A</option>
                                <option value="2">1° Ano B</option>
                                <option value="3">1° Ano C</option>
                                <option value="4">1° Ano D</option>
                                <option value="5">2° Ano A</option>
                                <option value="6">2° Ano B</option>
                                <option value="7">2° Ano C</option>
                                <option value="8">2° Ano D</option>
                                <option value="9">3° Ano A</option>
                                <option value="10">3° Ano B</option>
                                <option value="11">3° Ano C</option>
                                <option value="12">3° Ano D</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Período do Relatório</label>
                            <div class="radio-group">
                                <label class="radio-item">
                                    <input type="radio" name="tipo_relatorio" value="dia_atual" class="radio-input" required>
                                    <span class="radio-custom"></span>
                                    <span class="radio-label">Dia Atual</span>
                                </label>
                                <label class="radio-item">
                                    <input type="radio" name="tipo_relatorio" value="ultimos_30_dias" class="radio-input">
                                    <span class="radio-custom"></span>
                                    <span class="radio-label">Últimos 30 Dias</span>
                                </label>
                                <label class="radio-item">
                                    <input type="radio" name="tipo_relatorio" value="ultimos_12_meses" class="radio-input">
                                    <span class="radio-custom"></span>
                                    <span class="radio-label">Últimos 12 Meses</span>
                                </label>
                            </div>
                        </div>

                        <input type="hidden" name="GerarRelatorio" value="por_turmaEntrada">
                        <input type="hidden" name="form_id" value="entradaT">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-file-export"></i>
                            Gerar Relatório
                        </button>
                    </form>
                </div>
            </div>

            <!-- Info Card -->
            <div class="info-card">
                <div class="info-icon">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="info-content">
                    <h3>Informação</h3>
                    <p>Os relatórios de entrada mostram dados sobre o acesso dos estudantes ao sistema. Escolha o período desejado para análises mais precisas.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    

    

</section>

<script>
(() => {
// Initialize Select2 with enhanced options
        $(document).ready(function() {
            $('#relatorio-entrada .js-example-basic-single').select2({
                placeholder: "Buscar aluno...",
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return "Nenhum aluno encontrado";
                    },
                    searching: function() {
                        return "Buscando...";
                    }
                },
                templateResult: formatStudent,
                templateSelection: formatStudentSelection
            });

            // Custom formatting for student options
            function formatStudent(student) {
                if (!student.id) return student.text;
                
                return $(`
                    <div class="flex items-center gap-2 py-1">
                        <i class="fas fa-user-graduate text-ceara-green"></i>
                        <span>${student.text}</span>
                    </div>
                `);
            }

            function formatStudentSelection(student) {
                if (!student.id) return student.text;
                
                return $(`
                    <div class="flex items-center gap-2">
                        <i class="fas fa-user-graduate text-ceara-green"></i>
                        <span>${student.text}</span>
                    </div>
                `);
            }

            // Add hover effect to select container
            $('.select2-container').hover(
                function() {
                    $(this).find('.select2-selection--single').addClass('hover');
                },
                function() {
                    $(this).find('.select2-selection--single').removeClass('hover');
                }
            );
        });

        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const section = document.getElementById('relatorio-entrada');
            const tabBtns = section.querySelectorAll('.tab-btn');
            const tabContents = section.querySelectorAll('.tab-content');

            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-tab');

                    // Remove active class from all tabs and contents
                    tabBtns.forEach(b => b.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));

                    // Add active class to clicked tab and corresponding content
                    this.classList.add('active');
                    section.querySelector('#relatorio-entrada-tab-' + targetTab).classList.add('active');
                });
            });

            // Form validation
            const forms = section.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const radios = form.querySelectorAll('input[type="radio"]');
                    const selects = form.querySelectorAll('select');
                    let isValid = true;

                    // Check radio buttons
                    const radioGroups = {};
                    radios.forEach(radio => {
                        const name = radio.name;
                        if (!radioGroups[name]) radioGroups[name] = [];
                        radioGroups[name].push(radio);
                    });

                    Object.values(radioGroups).forEach(group => {
                        const checked = group.some(radio => radio.checked);
                        if (!checked) isValid = false;
                    });

                    // Check selects
                    selects.forEach(select => {
                        if (!select.value) {
                            isValid = false;
                            select.style.borderColor = '#ef4444';
                            setTimeout(() => {
                                select.style.borderColor = '#e5e7eb';
                            }, 2000);
                        }
                    });

                    if (!isValid) {
                        e.preventDefault();
                        alert('Por favor, preencha todos os campos obrigatórios.');
                    } else {
                        // Show loading state
                        const button = this.querySelector('.btn-primary');
                        button.disabled = true;
                        button.innerHTML = '<div style="width: 16px; height: 16px; border: 2px solid #ffffff; border-top: 2px solid transparent; border-radius: 50%; animation: spin 1s linear infinite; margin-right: 8px;"></div> Gerando...';
                    }
                });
            });
        });

        // Add spin animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
})();
</script>

<section class="page-section" id="relatorio-saida" aria-label="Relatório de Saída">

    

    <div class="flex-1 container mx-auto px-4 py-8 mt-16">
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold mb-2">
                    <span class="gradient-text">Relatório de Saída</span>
                </h1>
                <p class="text-gray-600">Selecione o tipo de relatório que deseja gerar</p>
            </div>

            <div class="space-y-8">
                <!-- Relatório por Aluno -->
                <div class="report-card p-6">
                    <div class="card-icon aluno">
                        <i class="fas fa-user-graduate text-xl"></i>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Por Aluno</h2>
                    <form target="_blank" id="relatorio-saida-form" action="../control/control_index.php" method="POST" class="space-y-4">
                        <select id="relatorio-saida-id_aluno" name="id_aluno" class="select-field" required>
                            <option value="" disabled selected>Selecione o Nome do Aluno</option>
                            <?php
                            $dados = $select->select_alunos();
                            foreach ($dados as $dado) {
                            ?>
                                <option value="<?= $dado['id_aluno'] ?>"><?= $dado['nome'] ?></option>
                            <?php
                            }
                            ?>
                        </select>

                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="tipo_relatorio" value="dia_atual" required>
                                <span>Dia Atual</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="tipo_relatorio" value="ultimos_30_dias">
                                <span>Últimos 30 Dias</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="tipo_relatorio" value="ultimos_12_meses">
                                <span>Últimos 12 Meses</span>
                            </label>
                        </div>

                        <input type="hidden" name="GerarRelatorio" value="por_alunoSaida">
                        <input type="hidden" name="form_id" value="entrada">
                        <div class="flex justify-center">
                            <button type="submit" name="btn" class="btn-submit">
                                <i class="fas fa-file-alt"></i>
                                Gerar Relatório
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Relatório por Ano -->
                <div class="report-card p-6">
                    <div class="card-icon ano">
                        <i class="fas fa-calendar-alt text-xl"></i>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Por Ano</h2>
                    <form target="_blank" id="saidaA" action="../control/control_index.php" method="POST" class="space-y-4">
                        <select id="ano" name="Ano" class="select-field" required>
                            <option value="" disabled selected>Selecione o ano</option>
                            <option value="1">1° Anos</option>
                            <option value="2">2° Anos</option>
                            <option value="3">3° Anos</option>
                        </select>

                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="tipo_relatorio" value="dia_atual" required>
                                <span>Dia Atual</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="tipo_relatorio" value="ultimos_30_dias">
                                <span>Últimos 30 Dias</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="tipo_relatorio" value="ultimos_12_meses">
                                <span>Últimos 12 Meses</span>
                            </label>
                        </div>

                        <input type="hidden" name="GerarRelatorio" value="ano_geralSaida">
                        <input type="hidden" name="form_id" value="entradaA">
                        <div class="flex justify-center">
                            <button type="submit" name="btn" class="btn-submit">
                                <i class="fas fa-file-alt"></i>
                                Gerar Relatório
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Relatório por Turma -->
                <div class="report-card p-6">
                    <div class="card-icon turma">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Por Turma</h2>
                    <form target="_blank" id="saidaT" action="../control/control_index.php" method="POST" class="space-y-4">
                        <select id="Turma" name="Turma" class="select-field" required>
                            <option value="" disabled selected>Selecione a turma</option>
                            <option value="1">1° Ano A</option>
                            <option value="2">1° Ano B</option>
                            <option value="3">1° Ano C</option>
                            <option value="4">1° Ano D</option>
                            <option value="5">2° Ano A</option>
                            <option value="6">2° Ano B</option>
                            <option value="7">2° Ano C</option>
                            <option value="8">2° Ano D</option>
                            <option value="9">3° Ano A</option>
                            <option value="10">3° Ano B</option>
                            <option value="11">3° Ano C</option>
                            <option value="12">3° Ano D</option>
                        </select>

                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="tipo_relatorio" value="dia_atual" required>
                                <span>Dia Atual</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="tipo_relatorio" value="ultimos_30_dias">
                                <span>Últimos 30 Dias</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="tipo_relatorio" value="ultimos_12_meses">
                                <span>Últimos 12 Meses</span>
                            </label>
                        </div>

                        <input type="hidden" name="GerarRelatorio" value="por_turmaSaida">
                        <input type="hidden" name="form_id" value="saidaT">
                        <div class="flex justify-center">
                            <button type="submit" name="btn" class="btn-submit">
                                <i class="fas fa-file-alt"></i>
                                Gerar Relatório
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    

</section>

<section class="page-section" id="relatorio-estagio" aria-label="Relatório de Saídas-Estágio">

    
    

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Title Section -->
            <div class="title-section">
                <div class="icon-container">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <h1 class="gradient-text">Relatório de Saída-Estágio</h1>
                <p class="subtitle">Gere relatórios detalhados de saídas para estágio</p>
            </div>

            <!-- Form Card -->
            <div class="form-card">
                <!-- Tabs Navigation -->
                <div class="tabs-nav">
                    <button class="tab-btn active" data-tab="aluno">
                        <i class="fas fa-user"></i> Por Aluno
                    </button>
                    <button class="tab-btn" data-tab="ano">
                        <i class="fas fa-calendar-alt"></i> Por Ano
                    </button>
                    <button class="tab-btn" data-tab="turma">
                        <i class="fas fa-users"></i> Por Turma
                    </button>
                </div>

                <!-- Tab Contents -->
                <!-- Por Aluno -->
                <div class="tab-content active" id="relatorio-estagio-tab-aluno">
                    <form target="_blank" action="../control/control_index.php" method="POST">
                        <div class="form-group">
                            <label class="form-label">Selecione o Aluno</label>
                            <select class="js-example-basic-single form-select" name="id_aluno" required>
                                <option value="" disabled selected>Selecione o aluno</option>
                                <?php
                                $dados = $select->select_alunosE();
                                foreach ($dados as $dado) {
                                ?>
                                    <option value="<?= $dado['id_aluno'] ?>"><?= $dado['nome'] ?></option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Período do Relatório</label>
                            <div class="radio-group">
                                <label class="radio-item">
                                    <input type="radio" name="tipo_relatorio" value="dia_atual" class="radio-input" required>
                                    <span class="radio-custom"></span>
                                    <span class="radio-label">Dia Atual</span>
                                </label>
                                <label class="radio-item">
                                    <input type="radio" name="tipo_relatorio" value="ultimos_30_dias" class="radio-input">
                                    <span class="radio-custom"></span>
                                    <span class="radio-label">Últimos 30 Dias</span>
                                </label>
                                <label class="radio-item">
                                    <input type="radio" name="tipo_relatorio" value="ultimos_12_meses" class="radio-input">
                                    <span class="radio-custom"></span>
                                    <span class="radio-label">Últimos 12 Meses</span>
                                </label>
                            </div>
                        </div>

                        <input type="hidden" name="GerarRelatorio" value="por_aluno">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-file-export"></i>
                            Gerar Relatório
                        </button>
                    </form>
                </div>

                <!-- Por Ano -->
                <div class="tab-content" id="relatorio-estagio-tab-ano">
                    <form target="_blank" action="../control/control_index.php" method="POST">
                        <div class="form-group">
                            <div style="background: #dbeafe; border: 1px solid #93c5fd; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
                                <div style="display: flex; gap: 12px;">
                                    <i class="fas fa-info-circle" style="color: #2563eb; margin-top: 2px;"></i>
                                    <div>
                                        <h3 style="font-weight: 500; color: #1e40af; margin-bottom: 4px; font-size: 0.875rem;">Relatório do 3° Ano</h3>
                                        <p style="color: #1e40af; font-size: 0.75rem;">Este relatório mostrará dados de todas as turmas do 3° ano.</p>
                                    </div>
                                </div>
                            </div>

                            <label class="form-label">Período do Relatório</label>
                            <div class="radio-group">
                                <label class="radio-item">
                                    <input type="radio" name="tipo_relatorio" value="dia_atual" class="radio-input" required>
                                    <span class="radio-custom"></span>
                                    <span class="radio-label">Dia Atual</span>
                                </label>
                                <label class="radio-item">
                                    <input type="radio" name="tipo_relatorio" value="ultimos_30_dias" class="radio-input">
                                    <span class="radio-custom"></span>
                                    <span class="radio-label">Últimos 30 Dias</span>
                                </label>
                                <label class="radio-item">
                                    <input type="radio" name="tipo_relatorio" value="ultimos_12_meses" class="radio-input">
                                    <span class="radio-custom"></span>
                                    <span class="radio-label">Últimos 12 Meses</span>
                                </label>
                            </div>
                        </div>

                        <input type="hidden" name="GerarRelatorio" value="3_ano_geral">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-file-export"></i>
                            Gerar Relatório
                        </button>
                    </form>
                </div>

                <!-- Por Turma -->
                <div class="tab-content" id="relatorio-estagio-tab-turma">
                    <form target="_blank" action="../control/control_index.php" method="POST">
                        <div class="form-group">
                            <label class="form-label">Selecione a Turma</label>
                            <select name="Turma" class="form-select" required>
                                <option value="" disabled selected>Selecione a turma</option>
                                <option value="9">3° Ano A</option>
                                <option value="10">3° Ano B</option>
                                <option value="11">3° Ano C</option>
                                <option value="12">3° Ano D</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Período do Relatório</label>
                            <div class="radio-group">
                                <label class="radio-item">
                                    <input type="radio" name="tipo_relatorio" value="dia_atual" class="radio-input" required>
                                    <span class="radio-custom"></span>
                                    <span class="radio-label">Dia Atual</span>
                                </label>
                                <label class="radio-item">
                                    <input type="radio" name="tipo_relatorio" value="ultimos_30_dias" class="radio-input">
                                    <span class="radio-custom"></span>
                                    <span class="radio-label">Últimos 30 Dias</span>
                                </label>
                                <label class="radio-item">
                                    <input type="radio" name="tipo_relatorio" value="ultimos_12_meses" class="radio-input">
                                    <span class="radio-custom"></span>
                                    <span class="radio-label">Últimos 12 Meses</span>
                                </label>
                            </div>
                        </div>

                        <input type="hidden" name="GerarRelatorio" value="por_turma">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-file-export"></i>
                            Gerar Relatório
                        </button>
                    </form>
                </div>
            </div>

            <!-- Info Card -->
           
        </div>
    </div>

    <!-- Footer -->
    

    

</section>

<script>
(() => {
// Initialize Select2 with enhanced options
        $(document).ready(function() {
            $('#relatorio-estagio .js-example-basic-single').select2({
                placeholder: "Buscar aluno...",
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return "Nenhum aluno encontrado";
                    },
                    searching: function() {
                        return "Buscando...";
                    }
                },
                templateResult: formatStudent,
                templateSelection: formatStudentSelection
            });

            // Custom formatting for student options
            function formatStudent(student) {
                if (!student.id) return student.text;
                
                return $(`
                    <div class="flex items-center gap-2 py-1">
                        <i class="fas fa-user-graduate text-ceara-green"></i>
                        <span>${student.text}</span>
                    </div>
                `);
            }

            function formatStudentSelection(student) {
                if (!student.id) return student.text;
                
                return $(`
                    <div class="flex items-center gap-2">
                        <i class="fas fa-user-graduate text-ceara-green"></i>
                        <span>${student.text}</span>
                    </div>
                `);
            }

            // Add hover effect to select container
            $('.select2-container').hover(
                function() {
                    $(this).find('.select2-selection--single').addClass('hover');
                },
                function() {
                    $(this).find('.select2-selection--single').removeClass('hover');
                }
            );
        });

        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const section = document.getElementById('relatorio-estagio');
            const tabBtns = section.querySelectorAll('.tab-btn');
            const tabContents = section.querySelectorAll('.tab-content');

            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-tab');

                    // Remove active class from all tabs and contents
                    tabBtns.forEach(b => b.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));

                    // Add active class to clicked tab and corresponding content
                    this.classList.add('active');
                    section.querySelector('#relatorio-estagio-tab-' + targetTab).classList.add('active');
                });
            });

            // Form validation
            const forms = section.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const radios = form.querySelectorAll('input[type="radio"]');
                    const selects = form.querySelectorAll('select');
                    let isValid = true;

                    // Check radio buttons
                    const radioGroups = {};
                    radios.forEach(radio => {
                        const name = radio.name;
                        if (!radioGroups[name]) radioGroups[name] = [];
                        radioGroups[name].push(radio);
                    });

                    Object.values(radioGroups).forEach(group => {
                        const checked = group.some(radio => radio.checked);
                        if (!checked) isValid = false;
                    });

                    // Check selects
                    selects.forEach(select => {
                        if (!select.value) isValid = false;
                    });

                    if (!isValid) {
                        e.preventDefault();
                        alert('Por favor, preencha todos os campos obrigatórios.');
                    }
                });
            });
        });
})();
</script>

<section class="page-section" id="relatorio-dia" aria-label="Relatório por Dia">

  

  <div class="flex-1 container mx-auto px-4 py-8 mt-16">
    <div class="max-w-4xl mx-auto">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold mb-2">
          <span class="gradient-text">Relatório por Dia</span>
        </h1>
        <p class="text-gray-600">Selecione a data para gerar o relatório de entradas e saídas</p>
      </div>

      <div class="form-card p-6 flex flex-col items-center gap-4">
        <form target="_blank" action="relatorios/alunos_geral_dia.php" method="post" class="flex flex-col sm:flex-row gap-4 items-center">
          <div class="flex items-center gap-2">
            <i class="fas fa-calendar-alt text-xl text-gray-600"></i>
            <input type="date" name="data" id="relatorio-dia-data" required>
          </div>
          <button type="submit">Gerar Relatório</button>
        </form>
      </div>
    </div>
  </div>

  

</section>

<section class="page-section" id="qrcode" aria-label="Gerar QR Codes">

    
    

    <!-- Main Content -->
    <div class="flex-1 flex items-center justify-center px-4 pt-16">
        <div class="max-w-md w-full slide-in">
            <!-- Title Section -->
            <div class="text-center mb-8">
                <div class="icon-container mx-auto mb-4">
                    <i class="fas fa-qrcode text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold mb-2">
                    <span class="gradient-text">Seleção de Turma</span>
                </h1>
                <p class="text-gray-600 text-sm">Selecione a turma para gerar o QR Code</p>
            </div>

            <!-- Form Container -->
            <div class="form-card p-8 border-t-4 border-ceara-green">
                <form target="_blank" id="turmaForm" action="QRCode/qrcode.php" method="post" class="space-y-6">
                    <!-- Turma Selection -->
                    <div class="space-y-2">
                        <label for="turma" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-users mr-2 text-ceara-green"></i>
                            Turma
                        </label>
                        <select 
                            name="turma" 
                            id="turmajs" 
                            class="w-full p-4 border-2 border-gray-200 rounded-lg focus:border-ceara-green input-focus-ring outline-none transition-all duration-300 bg-white text-sm"
                            required
                        >
                            <option value="">Selecione uma turma</option>
                            <option value="9">3° ano A</option>
                            <option value="10">3° ano B</option>
                            <option value="11">3° ano C</option>
                            <option value="12">3° ano D</option>
                            <option value="13">Area Dev</option>
                            <option value="14">Suporte TI</option>
                            <option value="15">Reimpressão Chachás</option>
                        </select>
                        <div id="qrcode-turma-error" class="hidden text-red-500 text-xs flex items-center mt-1">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            <span>Por favor, selecione uma turma</span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        id="qrcode-submitBtn"
                        class="w-full btn-primary text-white font-medium py-4 px-6 rounded-lg focus:outline-none focus:ring-4 focus:ring-green-200 disabled:opacity-50 disabled:cursor-not-allowed text-sm"
                    >
                        <span id="qrcode-submitText" class="flex items-center justify-center gap-2">
                            <i class="fas fa-qrcode"></i>
                            Gerar QR Code
                        </span>
                        <span id="qrcode-submitLoading" class="hidden flex items-center justify-center gap-2">
                            <div class="loading-spinner"></div>
                            Gerando...
                        </span>
                    </button>
                </form>

                <!-- Back Link -->
                <div class="text-center mt-6">
                    <a href="#" data-section-target="relatorios" class="inline-flex items-center gap-2 text-ceara-green hover:text-ceara-light-green font-medium transition-colors duration-300 text-sm">
                        <i class="fas fa-arrow-left text-sm"></i>
                        Voltar ao Menu
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    

    

</section>

<script>
(() => {
class TurmaSelector {
            constructor() {
                this.form = document.getElementById('turmaForm');
                this.select = document.getElementById('turmajs');
                this.submitBtn = document.getElementById('qrcode-submitBtn');
                this.init();
            }

            init() {
                // Add event listeners
                this.select.addEventListener('change', () => this.validateSelection());
                this.form.addEventListener('submit', (e) => this.handleSubmit(e));

                // Add visual feedback on select change
                this.select.addEventListener('change', () => {
                    if (this.select.value) {
                        this.select.classList.add('border-green-500');
                        this.select.classList.remove('border-gray-200');
                    } else {
                        this.select.classList.remove('border-green-500');
                        this.select.classList.add('border-gray-200');
                    }
                });
            }

            validateSelection() {
                const errorElement = document.getElementById('qrcode-turma-error');
                
                if (!this.select.value) {
                    errorElement.classList.remove('hidden');
                    return false;
                } else {
                    errorElement.classList.add('hidden');
                    return true;
                }
            }

            handleSubmit(e) {
                if (!this.validateSelection()) {
                    e.preventDefault();
                    
                    // Shake effect for invalid selection
                    this.select.classList.add('border-red-500');
                    this.select.style.animation = 'shake 0.5s ease-in-out';
                    
                    setTimeout(() => {
                        this.select.style.animation = '';
                        this.select.classList.remove('border-red-500');
                    }, 500);
                    
                    return;
                }

                // Show loading state
                this.showLoadingState();
            }

            showLoadingState() {
                const submitText = document.getElementById('qrcode-submitText');
                const submitLoading = document.getElementById('qrcode-submitLoading');
                
                submitText.classList.add('hidden');
                submitLoading.classList.remove('hidden');
                this.submitBtn.disabled = true;

                // Add pulse effect to the form
                document.getElementById('qrcode').querySelector('.form-card').classList.add('pulse-effect');
            }
        }

        // Add shake animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
        `;
        document.head.appendChild(style);

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new TurmaSelector();
        });

        // Add smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth';
})();
</script>

<section class="page-section" id="atrasos" aria-label="Atrasos registrados"></section>

<section class="page-section" id="ultimas-saidas" aria-label="Últimas Saídas">

    <!-- QR Code Reader -->
    <div id="reader" style="position: fixed; top: 20px; right: 20px; z-index: 1000;"></div>
    <input type="text" id="urlInput" placeholder="URL será inserida aqui automaticamente" style="position: fixed; top: -100px; opacity: 0;" />

    <div class="main-container max-w-7xl mx-auto p-6 lg:p-8 ">
        
        <div class="text-center mb-8">
            <!-- Botão Voltar -->
            <div class="flex justify-start mb-4">
                <button type="button" data-section-target="inicio" class="inline-flex items-center text-gray-600 bg-white rounded-lg px-4 py-2 shadow-sm border hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2 text-ceara-green"></i>
                    <span class="text-sm">Voltar</span>
                </button>
            </div>
            
            <div class="flex flex-col lg:flex-row items-center justify-center gap-4">
                <h1 class="text-2xl lg:text-3xl font-semibold">
                    <span class="gradient-text">Frequencia em tempo real </span>
                </h1>
                
                
                <div class="inline-flex items-center bg-white rounded-lg px-4 py-2 shadow-sm border min-w-[235px] justify-center">
                    <span id="relogio" 
                          class="text-base font-bold text-gray-700 tabular-nums" 
                          style="font-family:sans-serif; font-size:20px;Letter-spacing:1px;">
                    </span>
                    
                    
                    
                </div>
            </div>
            <div class="mt-4 text-sm text-gray-500">
                <i class="fas fa-info-circle mr-1"></i>
                No caso de problemas com o registro da frequencia, procure a recepção.
            </div>
        </div>
        
        
        
        
        
        

        <!-- Vista Desktop (Tabelas) -->   
        
        <div class="desktop-view">
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <!-- 3º Ano A -->
                <div class="table-container turma-3a">
                    <div class="table-header">
                        <div class="flex items-center justify-between mb-2">
                            <h2 class="text-lg font-semibold flex items-center">
                                <i class="fas fa-users mr-2"></i>
                                3º Ano A
                            </h2>
                            <?php
                            $dados_3a = $select->saida_estagio_3A();
                            $count_3a = count($dados_3a);
                            ?>
                            <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-medium">
                                <?= $count_3a ?> alunos
                            </span>
                        </div>
                        <input type="text" class="search-input search-3a" placeholder="Buscar aluno..." onkeyup="filterTable('3a')">
                    </div>
                    <div class="table-content custom-scrollbar">
                        <table class="w-full compact-table" id="table-3a">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-user mr-1"></i>Nome do Aluno
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-clock mr-1"></i>Horário
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($dados_3a as $dado) { ?>
                                    <tr class="table-row">
                                        <td class="px-4 py-2 text-sm text-gray-900">
                                            <div class="flex items-center">
                                                <i class="fas fa-user-graduate mr-2 text-danger"></i>
                                                <?= htmlspecialchars($dado['nome']) ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-500">
                                            <?= isset($dado['dae']) ? date('H:i', strtotime($dado['dae'])) : '--:--' ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                                <?php if (empty($dados_3a)) { ?>
                                    <tr>
                                        <td colspan="2" class="px-4 py-8 text-center text-gray-500 italic">
                                            Nenhum aluno registrado hoje
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 3º Ano B -->
                <div class="table-container turma-3b">
                    <div class="table-header">
                        <div class="flex items-center justify-between mb-2">
                            <h2 class="text-lg font-semibold flex items-center">
                                <i class="fas fa-users mr-2"></i>
                                3º Ano B
                            </h2>
                            <?php
                            $dados_3b = $select->saida_estagio_3B();
                            $count_3b = count($dados_3b);
                            ?>
                            <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-medium">
                                <?= $count_3b ?> alunos
                            </span>
                        </div>
                        <input type="text" class="search-input search-3b" placeholder="Buscar aluno..." onkeyup="filterTable('3b')">
                    </div>
                    <div class="table-content custom-scrollbar">
                        <table class="w-full compact-table" id="table-3b">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-user mr-1"></i>Nome do Aluno
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-clock mr-1"></i>Horário
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($dados_3b as $dado) { ?>
                                    <tr class="table-row">
                                        <td class="px-4 py-2 text-sm text-gray-900">
                                            <div class="flex items-center">
                                                <i class="fas fa-user-graduate mr-2 text-info"></i>
                                                <?= htmlspecialchars($dado['nome']) ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-500">
                                            <?= isset($dado['dae']) ? date('H:i', strtotime($dado['dae'])) : '--:--' ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                                <?php if (empty($dados_3b)) { ?>
                                    <tr>
                                        <td colspan="2" class="px-4 py-8 text-center text-gray-500 italic">
                                            Nenhum aluno registrado hoje
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 3º Ano C -->
                <div class="table-container turma-3c">
                    <div class="table-header">
                        <div class="flex items-center justify-between mb-2">
                            <h2 class="text-lg font-semibold flex items-center">
                                <i class="fas fa-users mr-2"></i>
                                3º Ano C
                            </h2>
                            <?php
                            $dados_3c = $select->saida_estagio_3C();
                            $count_3c = count($dados_3c);
                            ?>
                            <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-medium">
                                <?= $count_3c ?> alunos
                            </span>
                        </div>
                        <input type="text" class="search-input search-3c" placeholder="Buscar aluno..." onkeyup="filterTable('3c')">
                    </div>
                    <div class="table-content custom-scrollbar">
                        <table class="w-full compact-table" id="table-3c">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-user mr-1"></i>Nome do Aluno
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-clock mr-1"></i>Horário
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($dados_3c as $dado) { ?>
                                    <tr class="table-row">
                                        <td class="px-4 py-2 text-sm text-gray-900">
                                            <div class="flex items-center">
                                                <i class="fas fa-user-graduate mr-2 text-admin"></i>
                                                <?= htmlspecialchars($dado['nome']) ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-500">
                                            <?= isset($dado['dae']) ? date('H:i', strtotime($dado['dae'])) : '--:--' ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                                <?php if (empty($dados_3c)) { ?>
                                    <tr>
                                        <td colspan="2" class="px-4 py-8 text-center text-gray-500 italic">
                                            Nenhum aluno registrado hoje
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 3º Ano D -->
                <div class="table-container turma-3d">
                    <div class="table-header">
                        <div class="flex items-center justify-between mb-2">
                            <h2 class="text-lg font-semibold flex items-center">
                                <i class="fas fa-users mr-2"></i>
                                3º Ano D
                            </h2>
                            <?php
                            $dados_3d = $select->saida_estagio_3D();
                            $count_3d = count($dados_3d);
                            ?>
                            <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-medium">
                                <?= $count_3d ?> alunos
                            </span>
                        </div>
                        <input type="text" class="search-input search-3d" placeholder="Buscar aluno..." onkeyup="filterTable('3d')">
                    </div>
                    <div class="table-content custom-scrollbar">
                        <table class="w-full compact-table" id="table-3d">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-user mr-1"></i>Nome do Aluno
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-clock mr-1"></i>Horário
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($dados_3d as $dado) { ?>
                                    <tr class="table-row">
                                        <td class="px-4 py-2 text-sm text-gray-900">
                                            <div class="flex items-center">
                                                <i class="fas fa-user-graduate mr-2 text-grey"></i>
                                                <?= htmlspecialchars($dado['nome']) ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-500">
                                            <?= isset($dado['dae']) ? date('H:i', strtotime($dado['dae'])) : '--:--' ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                                <?php if (empty($dados_3d)) { ?>
                                    <tr>
                                        <td colspan="2" class="px-4 py-8 text-center text-gray-500 italic">
                                            Nenhum aluno registrado hoje
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vista Mobile (Cards) -->
        <div class="mobile-view">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <!-- 3º Ano A -->
                <div class="class-card">
                    <div class="card-header-3a p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h2 class="text-lg font-semibold flex items-center text-white">
                                <i class="fas fa-users mr-2"></i>
                                3º Ano A
                            </h2>
                            <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-medium text-white">
                                <?= $count_3a ?>
                            </span>
                        </div>
                        <input type="text" class="search-input search-mobile-3a" placeholder="Buscar aluno..." onkeyup="filterCards('3a')">
                    </div>
                    <div class="p-4 compact-cards custom-scrollbar" id="cards-3a">
                        <?php if ($count_3a > 0) { ?>
                            <?php foreach ($dados_3a as $index => $dado) { ?>
                                <div class="student-card compact-card">
                                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                                        <div class="flex items-center mb-2 md:mb-0">
                                            <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-red-100 text-danger text-sm font-medium">
                                                <?= $index + 1 ?>
                                            </div>
                                            <span class="ml-3 font-medium text-gray-900 text-base"><?= htmlspecialchars($dado['nome']) ?></span>
                                        </div>
                                        <div class="text-sm text-gray-500 flex items-center">
                                            <i class="fas fa-clock mr-2"></i>
                                            <span class="font-medium"><?= isset($dado['dae']) ? date('H:i', strtotime($dado['dae'])) : '--:--' ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php if ($count_3a > 10) { ?>
                                <div class="pagination" id="pagination-3a">
                                    <!-- Paginação será gerada via JavaScript -->
                                </div>
                            <?php } ?>
                        <?php } else { ?>
                            <div class="text-center py-8 text-gray-500 italic">
                                Nenhum aluno registrado hoje
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <!-- 3º Ano B -->
                <div class="class-card">
                    <div class="card-header-3b p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h2 class="text-lg font-semibold flex items-center text-white">
                                <i class="fas fa-users mr-2"></i>
                                3º Ano B
                            </h2>
                            <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-medium text-white">
                                <?= $count_3b ?>
                            </span>
                        </div>
                        <input type="text" class="search-input search-mobile-3b" placeholder="Buscar aluno..." onkeyup="filterCards('3b')">
                    </div>
                    <div class="p-4 compact-cards custom-scrollbar" id="cards-3b">
                        <?php if ($count_3b > 0) { ?>
                            <?php foreach ($dados_3b as $index => $dado) { ?>
                                <div class="student-card compact-card">
                                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                                        <div class="flex items-center mb-2 md:mb-0">
                                            <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-blue-100 text-info text-sm font-medium">
                                                <?= $index + 1 ?>
                                            </div>
                                            <span class="ml-3 font-medium text-gray-900 text-base"><?= htmlspecialchars($dado['nome']) ?></span>
                                        </div>
                                        <div class="text-sm text-gray-500 flex items-center">
                                            <i class="fas fa-clock mr-2"></i>
                                            <span class="font-medium"><?= isset($dado['dae']) ? date('H:i', strtotime($dado['dae'])) : '--:--' ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php if ($count_3b > 10) { ?>
                                <div class="pagination" id="pagination-3b">
                                    <!-- Paginação será gerada via JavaScript -->
                                </div>
                            <?php } ?>
                        <?php } else { ?>
                            <div class="text-center py-8 text-gray-500 italic">
                                Nenhum aluno registrado hoje
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <!-- 3º Ano C -->
                <div class="class-card">
                    <div class="card-header-3c p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h2 class="text-lg font-semibold flex items-center text-white">
                                <i class="fas fa-users mr-2"></i>
                                3º Ano C
                            </h2>
                            <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-medium text-white">
                                <?= $count_3c ?>
                            </span>
                        </div>
                        <input type="text" class="search-input search-mobile-3c" placeholder="Buscar aluno..." onkeyup="filterCards('3c')">
                    </div>
                    <div class="p-4 compact-cards custom-scrollbar" id="cards-3c">
                        <?php if ($count_3c > 0) { ?>
                            <?php foreach ($dados_3c as $index => $dado) { ?>
                                <div class="student-card compact-card">
                                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                                        <div class="flex items-center mb-2 md:mb-0">
                                            <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-cyan-100 text-admin text-sm font-medium">
                                                <?= $index + 1 ?>
                                            </div>
                                            <span class="ml-3 font-medium text-gray-900 text-base"><?= htmlspecialchars($dado['nome']) ?></span>
                                        </div>
                                        <div class="text-sm text-gray-500 flex items-center">
                                            <i class="fas fa-clock mr-2"></i>
                                            <span class="font-medium"><?= isset($dado['dae']) ? date('H:i', strtotime($dado['dae'])) : '--:--' ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php if ($count_3c > 10) { ?>
                                <div class="pagination" id="pagination-3c">
                                    <!-- Paginação será gerada via JavaScript -->
                                </div>
                            <?php } ?>
                        <?php } else { ?>
                            <div class="text-center py-8 text-gray-500 italic">
                                Nenhum aluno registrado hoje
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <!-- 3º Ano D -->
                <div class="class-card">
                    <div class="card-header-3d p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h2 class="text-lg font-semibold flex items-center text-white">
                                <i class="fas fa-users mr-2"></i>
                                3º Ano D
                            </h2>
                            <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-medium text-white">
                                <?= $count_3d ?>
                            </span>
                        </div>
                        <input type="text" class="search-input search-mobile-3d" placeholder="Buscar aluno..." onkeyup="filterCards('3d')">
                    </div>
                    <div class="p-4 compact-cards custom-scrollbar" id="cards-3d">
                        <?php if ($count_3d > 0) { ?>
                            <?php foreach ($dados_3d as $index => $dado) { ?>
                                <div class="student-card compact-card">
                                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                                        <div class="flex items-center mb-2 md:mb-0">
                                            <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-grey text-sm font-medium">
                                                <?= $index + 1 ?>
                                            </div>
                                            <span class="ml-3 font-medium text-gray-900 text-base"><?= htmlspecialchars($dado['nome']) ?></span>
                                        </div>
                                        <div class="text-sm text-gray-500 flex items-center">
                                            <i class="fas fa-clock mr-2"></i>
                                            <span class="font-medium"><?= isset($dado['dae']) ? date('H:i', strtotime($dado['dae'])) : '--:--' ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php if ($count_3d > 10) { ?>
                                <div class="pagination" id="pagination-3d">
                                    <!-- Paginação será gerada via JavaScript -->
                                </div>
                            <?php } ?>
                        <?php } else { ?>
                            <div class="text-center py-8 text-gray-500 italic">
                                Nenhum aluno registrado hoje
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>



        <!-- Footer com botão de atualização manual -->
        <!-- Coloquei a atualização na função que registra a saída, por isso documentei este botão. Otávio 18/06/2026 -->
        <!-- 
        <div class="text-center mt-8">
            <button type="button" data-refresh-section="ultimas-saidas" class="inline-flex items-center text-gray-600 bg-white rounded-lg px-4 py-2 shadow-sm border hover:bg-gray-50 transition-colors">
                <i class="fas fa-sync-alt mr-2 text-ceara-green"></i>
                <span class="text-sm">Atualizar dados</span>
            </button>
        </div>
        -->
    <br>
    </div>

    <!-- Footer geométrico -->
    <div class="geometric-footer">
        <div class="geometric-shape shape-1"></div>
        <div class="geometric-shape shape-2"></div>
        <div class="geometric-shape shape-3"></div>
        <div class="geometric-shape shape-4"></div>
        <div class="geometric-shape shape-5"></div>
        <div class="geometric-shape shape-6"></div>
    </div>

    
    

</section>

<script>
(() => {
function atualizarRelogio() {
                            const agora = new Date();
                            const dataHora = `${agora.getDate().toString().padStart(2,'0')}/${(agora.getMonth()+1).toString().padStart(2,'0')}/${agora.getFullYear()} | `
                                          + `${agora.getHours().toString().padStart(2,'0')}:${agora.getMinutes().toString().padStart(2,'0')}:${agora.getSeconds().toString().padStart(2,'0')}`;
                            
                            document.getElementById('relogio').textContent = dataHora;
                        }
                
                        atualizarRelogio();
                        setInterval(atualizarRelogio, 1000);
})();
</script>

<script>
(() => {
// Função para filtrar tabelas
        function filterTable(turma) {
            const input = document.querySelector(`.search-${turma}`);
            const filter = input.value.toUpperCase();
            const table = document.getElementById(`table-${turma}`);
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) { // Começar do 1 para pular o cabeçalho
                const nameCell = rows[i].getElementsByTagName('td')[0];
                if (nameCell) {
                    const nameText = nameCell.textContent || nameCell.innerText;
                    if (nameText.toUpperCase().indexOf(filter) > -1) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }
        }

        // Função para filtrar cards
        function filterCards(turma) {
            const input = document.querySelector(`.search-mobile-${turma}`);
            const filter = input.value.toUpperCase();
            const container = document.getElementById(`cards-${turma}`);
            const cards = container.getElementsByClassName('student-card');

            for (let i = 0; i < cards.length; i++) {
                const nameText = cards[i].querySelector('span').textContent || cards[i].querySelector('span').innerText;
                if (nameText.toUpperCase().indexOf(filter) > -1) {
                    cards[i].style.display = '';
                } else {
                    cards[i].style.display = 'none';
                }
            }
        }

        // Função para inicializar paginação
        function initPagination() {
            const turmas = ['3a', '3b', '3c', '3d'];
            const itemsPerPage = 10;

            turmas.forEach(turma => {
                const container = document.getElementById(`cards-${turma}`);
                if (!container) return;

                const cards = container.getElementsByClassName('student-card');
                const totalPages = Math.ceil(cards.length / itemsPerPage);

                if (totalPages <= 1) return;

                const paginationContainer = document.getElementById(`pagination-${turma}`);
                if (!paginationContainer) return;

                // Criar botões de paginação
                let paginationHTML = '';
                for (let i = 1; i <= totalPages; i++) {
                    paginationHTML += `<span class="pagination-btn ${i === 1 ? 'active' : ''}" data-page="${i}">${i}</span>`;
                }
                paginationContainer.innerHTML = paginationHTML;

                // Mostrar apenas a primeira página inicialmente
                showPage(turma, 1, itemsPerPage);

                // Adicionar event listeners aos botões
                const buttons = paginationContainer.getElementsByClassName('pagination-btn');
                for (let i = 0; i < buttons.length; i++) {
                    buttons[i].addEventListener('click', function() {
                        const page = parseInt(this.getAttribute('data-page'));
                        showPage(turma, page, itemsPerPage);

                        // Atualizar classe ativa
                        for (let j = 0; j < buttons.length; j++) {
                            buttons[j].classList.remove('active');
                        }
                        this.classList.add('active');
                    });
                }
            });
        }

        // Função para mostrar uma página específica
        function showPage(turma, page, itemsPerPage) {
            const container = document.getElementById(`cards-${turma}`);
            const cards = container.getElementsByClassName('student-card');

            const startIndex = (page - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;

            for (let i = 0; i < cards.length; i++) {
                if (i >= startIndex && i < endIndex) {
                    cards[i].style.display = '';
                } else {
                    cards[i].style.display = 'none';
                }
            }
        }

        window.filterTable = filterTable;
        window.filterCards = filterCards;

        // Inicializar paginação quando o documento estiver pronto
        document.addEventListener('DOMContentLoaded', function() {
            initPagination();
        });
})();
</script>

<script>
(() => {
// QR Code Reader functionality
        const input = document.getElementById('urlInput');
        const readerDiv = document.getElementById('reader');
        let leituraAtiva = false;
        let leitorIniciando = false;
        let ultimaUrlAberta = null;
        let html5QrCode;

        // Função para manter o input sempre focado
        function manterFoco() {
            if (!leituraAtiva) return;
            input.focus();
            setTimeout(manterFoco, 100);
        }

        function abrirEmNovaAba(url) {
            if (!url || url === ultimaUrlAberta) return;
            if (!url.startsWith('http://') && !url.startsWith('https://')) {
                url = 'https://' + url;
            }
            ultimaUrlAberta = url;
            window.open(url, '_blank', 'noopener');
        }

        function onQRCodeScanned(decodedText) {
            input.value = decodedText;
            abrirEmNovaAba(decodedText);
        }

        // Inicia o leitor QR automaticamente quando a página carrega
        document.addEventListener('section:activate:ultimas-saidas', () => {
            if (leituraAtiva || leitorIniciando) return;

            // Limpa o histórico de URLs abertas
            ultimaUrlAberta = null;

            // Inicia o leitor QR apenas em dispositivos não-mobile (>= 768px)
            if (window.innerWidth >= 768) { 
                readerDiv.style.display = 'block';
                html5QrCode = new Html5Qrcode("reader");
                leitorIniciando = true;

                html5QrCode.start(
                    { facingMode: "environment" },
                    { fps: 10, qrbox: 250 },
                    onQRCodeScanned
                ).then(() => {
                    leitorIniciando = false;
                    if (!document.getElementById('ultimas-saidas').classList.contains('active')) {
                        const scanner = html5QrCode;
                        html5QrCode = null;
                        scanner.stop().then(() => scanner.clear()).catch(() => {});
                        return;
                    }
                    leituraAtiva = true;
                    input.focus();
                    manterFoco();
                }).catch(() => {
                    leitorIniciando = false;
                    leituraAtiva = false;
                    html5QrCode = null;
                    readerDiv.style.display = 'none';
                });
            } else {
                readerDiv.style.display = 'none'; // Hide the reader div on mobile
                leituraAtiva = false;
            }
        });

        document.addEventListener('section:deactivate:ultimas-saidas', () => {
            leituraAtiva = false;
            readerDiv.style.display = 'none';
            if (html5QrCode && !leitorIniciando) {
                const scanner = html5QrCode;
                html5QrCode = null;
                scanner.stop().then(() => scanner.clear()).catch(() => {});
            }
        });

        // Adiciona evento para abrir URL quando o usuário digita
        let timeoutId;
        input.addEventListener('input', (e) => {
            const url = e.target.value.trim();
            if (url) {
                if (timeoutId) clearTimeout(timeoutId);
                timeoutId = setTimeout(() => {
                    abrirEmNovaAba(url);
                }, 100);
            }
        });

        // Previne que o usuário perca o foco
        readerDiv.addEventListener('click', () => input.focus());

        // Força recarregamento da página se vier do cache
        

        // Limpa o cache quando a página carrega
        
})();
</script>

<section class="page-section" id="cadastro" aria-label="Cadastrar Aluno">

    
    

    <!-- Main Content -->
    <div class="flex-1 container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto">
            <!-- Title Section -->
            <div class="text-center mb-6">
                <div class="icon-container mx-auto mb-3">
                    <i class="fas fa-user-plus text-lg"></i>
                </div>
                <h1 class="text-2xl font-bold mb-2">
                    <span class="gradient-text">Cadastro de Aluno</span>
                </h1>
                <p class="text-gray-600 text-sm">Adicione um novo aluno ao sistema</p>
            </div>

            <!-- Success Message -->
            <div id="cadastro-successMessage" class="hidden mb-4 p-3 bg-green-50 border border-green-200 rounded-lg slide-in">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 mr-2 text-sm"></i>
                    <span class="text-green-700 font-medium text-sm">Aluno cadastrado com sucesso!</span>
                </div>
            </div>

            <!-- Form Container -->
            <div class="form-card p-6 border-t-4 border-ceara-green">
                <form target="_blank" id="cadastroForm" action="../control/control_index.php" method="post" class="space-y-5">
                    <input type="hidden" name="cadastrar" value="1">

                    <!-- Nome Field -->
                    <div class="input-group relative pt-2">
                        <label class="floating-label absolute left-3 top-5 text-gray-500 text-sm">
                            Nome Completo
                        </label>
                        <input 
                            type="text" 
                            id="nome" 
                            name="nome" 
                            class="input-field w-full pt-4 pb-2 px-3 border-2 border-gray-200 rounded-lg focus:border-ceara-green input-focus-ring outline-none transition-all duration-300 text-sm"
                            required
                        >
                        <div class="absolute right-3 top-5 text-gray-400">
                            <i class="fas fa-user text-sm"></i>
                        </div>
                        <div id="nome-error" class="error-message hidden mt-1 text-red-500 text-xs flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            <span>Por favor, insira um nome válido (apenas letras)</span>
                        </div>
                        <div id="nome-success" class="success-message hidden mt-1 text-green-600 text-xs flex items-center">
                            <i class="fas fa-check-circle mr-1"></i>
                            <span>Nome válido</span>
                        </div>
                    </div>

                    <!-- Matrícula Field -->
                    <div class="input-group relative pt-2">
                        <label class="floating-label absolute left-3 top-5 text-gray-500 text-sm">
                            Matrícula (7 dígitos)
                        </label>
                        <input 
                            type="text" 
                            id="matricula" 
                            name="matricula" 
                            maxlength="7"
                            class="input-field w-full pt-4 pb-2 px-3 border-2 border-gray-200 rounded-lg focus:border-ceara-green input-focus-ring outline-none transition-all duration-300 text-sm"
                            required
                        >
                        <div class="absolute right-3 top-5 text-gray-400">
                            <i class="fas fa-id-card text-sm"></i>
                        </div>
                        <div id="matricula-error" class="error-message hidden mt-1 text-red-500 text-xs flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            <span>A matrícula deve conter exatamente 7 dígitos</span>
                        </div>
                        <div id="matricula-success" class="success-message hidden mt-1 text-green-600 text-xs flex items-center">
                            <i class="fas fa-check-circle mr-1"></i>
                            <span>Matrícula válida</span>
                        </div>
                    </div>

                    <!-- Turma Field -->
                    <div class="input-group relative">
                        <label for="id_turma" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-users mr-1 text-ceara-green text-sm"></i>
                            Turma
                        </label>
                        <select 
                            id="id_turma" 
                            name="id_turma" 
                            class="w-full p-3 border-2 border-gray-200 rounded-lg focus:border-ceara-green input-focus-ring outline-none transition-all duration-300 bg-white text-sm"
                            required
                        >
                            <option value="">Selecione uma turma</option>
                            <?php 
                            $dados = $select->select_turmas();

                            foreach($dados as $dado){
                            ?>
                            <option value="<?=$dado['id_turma']?>"><?=$dado['ano']?> <?=$dado['turma']?></option>
                            <?php }?>
                        </select>
                        <div id="cadastro-turma-error" class="error-message hidden mt-1 text-red-500 text-xs flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            <span>Por favor, selecione uma turma</span>
                        </div>
                    </div>

                    <!-- Curso Field -->
                    <div class="input-group relative">
                        <label for="id_curso" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-book mr-1 text-ceara-green text-sm"></i>
                            Curso
                        </label>
                        <select 
                            id="id_curso" 
                            name="id_curso" 
                            class="w-full p-3 border-2 border-gray-200 rounded-lg focus:border-ceara-green input-focus-ring outline-none transition-all duration-300 bg-white text-sm"
                            required
                        >
                            <option value="">Selecione um curso</option>
                            <?php 
                            $dados = $select->select_curso();
                            foreach($dados as $dado){
                            ?>
                            <option value="<?=$dado['id_curso']?>"><?=$dado['curso']?></option>
                            <?php }?>
                        </select>
                        <div id="curso-error" class="error-message hidden mt-1 text-red-500 text-xs flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            <span>Por favor, selecione um curso</span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        id="cadastro-submitBtn"
                        class="w-full btn-primary text-white font-medium py-3 px-4 rounded-lg focus:outline-none focus:ring-4 focus:ring-green-200 disabled:opacity-50 disabled:cursor-not-allowed text-sm mt-6"
                    >
                        <span id="cadastro-submitText" class="flex items-center justify-center gap-2">
                            <i class="fas fa-user-plus text-sm"></i>
                            Cadastrar Aluno
                        </span>
                        <span id="cadastro-submitLoading" class="hidden flex items-center justify-center gap-2">
                            <div class="loading-spinner"></div>
                            Cadastrando...
                        </span>
                    </button>
                </form>

                <!-- Back Link -->
                <div class="text-center mt-4">
                    <a href="#" data-section-target="inicio" class="inline-flex items-center gap-2 text-ceara-green hover:text-ceara-light-green font-medium transition-colors duration-300 text-sm">
                        <i class="fas fa-arrow-left text-sm"></i>
                        Voltar ao Menu
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    

    

</section>

<script>
(() => {
// Form validation and UX improvements
        class FormValidator {
            constructor() {
                this.form = document.getElementById('cadastroForm');
                this.fields = {
                    nome: document.getElementById('nome'),
                    matricula: document.getElementById('matricula'),
                    turma: document.getElementById('id_turma'),
                    curso: document.getElementById('id_curso')
                };
                this.submitBtn = document.getElementById('cadastro-submitBtn');
                this.init();
            }

            init() {
                // Add event listeners for real-time validation
                Object.keys(this.fields).forEach(fieldName => {
                    const field = this.fields[fieldName];
                    field.addEventListener('input', () => this.validateField(fieldName));
                    field.addEventListener('blur', () => this.validateField(fieldName));
                    field.addEventListener('focus', () => this.clearFieldError(fieldName));
                });

                // Handle floating labels
                this.handleFloatingLabels();

                // Form submission
                this.form.addEventListener('submit', (e) => this.handleSubmit(e));

                // Format matricula input
                this.fields.matricula.addEventListener('input', this.formatMatricula);
            }

            handleFloatingLabels() {
                const inputs = document.querySelectorAll('.input-group input');
                inputs.forEach(input => {
                    const group = input.closest('.input-group');
                    
                    input.addEventListener('input', () => {
                        if (input.value.trim() !== '') {
                            group.classList.add('has-value');
                        } else {
                            group.classList.remove('has-value');
                        }
                    });

                    input.addEventListener('focus', () => {
                        group.classList.add('has-value');
                    });

                    input.addEventListener('blur', () => {
                        if (input.value.trim() === '') {
                            group.classList.remove('has-value');
                        }
                    });

                    // Check initial value
                    if (input.value.trim() !== '') {
                        group.classList.add('has-value');
                    }
                });
            }

            formatMatricula(e) {
                // Only allow numbers
                e.target.value = e.target.value.replace(/\D/g, '');
            }

            validateField(fieldName) {
                const field = this.fields[fieldName];
                const value = field.value.trim();
                let isValid = true;
                let errorMessage = '';

                switch (fieldName) {
                    case 'nome':
                        if (!value) {
                            isValid = false;
                            errorMessage = 'Nome é obrigatório';
                        } else if (!/^[A-Za-zÀ-ÖØ-öø-ÿ\s]+$/.test(value)) {
                            isValid = false;
                            errorMessage = 'Nome deve conter apenas letras';
                        } else if (value.length < 2) {
                            isValid = false;
                            errorMessage = 'Nome deve ter pelo menos 2 caracteres';
                        }
                        break;

                    case 'matricula':
                        if (!value) {
                            isValid = false;
                            errorMessage = 'Matrícula é obrigatória';
                        } else if (!/^\d{7}$/.test(value)) {
                            isValid = false;
                            errorMessage = 'Matrícula deve conter exatamente 7 dígitos';
                        }
                        break;

                    case 'turma':
                        if (!value || value === '') {
                            isValid = false;
                            errorMessage = 'Selecione uma turma';
                        }
                        break;

                    case 'curso':
                        if (!value || value === '') {
                            isValid = false;
                            errorMessage = 'Selecione um curso';
                        }
                        break;
                }

                this.showFieldValidation(fieldName, isValid, errorMessage);
                return isValid;
            }

            showFieldValidation(fieldName, isValid, errorMessage) {
                const field = this.fields[fieldName];
                const errorElement = document.getElementById(`${fieldName}-error`);
                const successElement = document.getElementById(`${fieldName}-success`);

                // Remove previous states
                field.classList.remove('border-red-500', 'border-green-500', 'shake');
                
                if (errorElement) {
                    errorElement.classList.add('hidden');
                }
                if (successElement) {
                    successElement.classList.add('hidden');
                }

                if (!isValid && field.value.trim() !== '') {
                    // Show error
                    field.classList.add('border-red-500', 'shake');
                    if (errorElement) {
                        errorElement.querySelector('span').textContent = errorMessage;
                        errorElement.classList.remove('hidden');
                        errorElement.classList.add('slide-in');
                    }
                } else if (isValid && field.value.trim() !== '') {
                    // Show success
                    field.classList.add('border-green-500');
                    if (successElement) {
                        successElement.classList.remove('hidden');
                        successElement.classList.add('slide-in');
                    }
                }
            }

            clearFieldError(fieldName) {
                const field = this.fields[fieldName];
                const errorElement = document.getElementById(`${fieldName}-error`);
                
                field.classList.remove('border-red-500', 'shake');
                if (errorElement) {
                    errorElement.classList.add('hidden');
                }
            }

            validateForm() {
                let isFormValid = true;
                Object.keys(this.fields).forEach(fieldName => {
                    if (!this.validateField(fieldName)) {
                        isFormValid = false;
                    }
                });
                return isFormValid;
            }

            handleSubmit(e) {
                e.preventDefault();
                
                if (!this.validateForm()) {
                    // Shake the form container
                    const container = this.form.closest('.page-section').querySelector('.form-card');
                    container.classList.add('shake');
                    setTimeout(() => container.classList.remove('shake'), 500);
                    return;
                }

                // Show loading state
                this.showLoadingState();

                // Simulate form submission (replace with actual submission)
                setTimeout(() => {
                    this.showSuccessState();
                }, 2000);

                // Uncomment the line below for actual form submission
                // this.form.submit();
            }

            showLoadingState() {
                const submitText = document.getElementById('cadastro-submitText');
                const submitLoading = document.getElementById('cadastro-submitLoading');
                
                submitText.classList.add('hidden');
                submitLoading.classList.remove('hidden');
                this.submitBtn.disabled = true;
            }

            showSuccessState() {
                const successMessage = document.getElementById('cadastro-successMessage');
                const container = this.form.closest('.page-section').querySelector('.form-card');
                
                // Reset button state
                const submitText = document.getElementById('cadastro-submitText');
                const submitLoading = document.getElementById('cadastro-submitLoading');
                submitText.classList.remove('hidden');
                submitLoading.classList.add('hidden');
                this.submitBtn.disabled = false;

                // Show success message
                successMessage.classList.remove('hidden');
                container.classList.add('pulse-success');
                
                // Reset form
                this.form.reset();
                this.form.querySelectorAll('.input-group').forEach(group => {
                    group.classList.remove('has-value');
                });
                this.form.querySelectorAll('.border-green-500').forEach(field => {
                    field.classList.remove('border-green-500');
                });
                this.form.closest('.page-section').querySelectorAll('.success-message').forEach(msg => {
                    msg.classList.add('hidden');
                });

                // Hide success message after 5 seconds
                setTimeout(() => {
                    successMessage.classList.add('hidden');
                    container.classList.remove('pulse-success');
                }, 5000);
            }
        }
})();
</script>
<script>
(() => {
  const navItems = document.querySelectorAll('.nav-item[data-target]');
  const sections = document.querySelectorAll('.page-section');
    const sidebarGroups = document.querySelectorAll('.nav-group[data-sidebar-group]');
    const sidebarStorageKey = 'salaberga-sidebar-groups';
    let savedSidebarGroups = {};
    try {
        savedSidebarGroups = JSON.parse(localStorage.getItem(sidebarStorageKey) || '{}');
        if (!savedSidebarGroups || typeof savedSidebarGroups !== 'object') savedSidebarGroups = {};
    } catch {}
    sidebarGroups.forEach(group => {
        const groupKey = group.dataset.sidebarGroup;
        if (Object.prototype.hasOwnProperty.call(savedSidebarGroups, groupKey)) {
            group.open = savedSidebarGroups[groupKey] === true;
        }
        group.addEventListener('toggle', () => {
            savedSidebarGroups[groupKey] = group.open;
            try {
                localStorage.setItem(sidebarStorageKey, JSON.stringify(savedSidebarGroups));
            } catch {}
        });
    });
    const menuToggle = document.querySelector('.mobile-menu');
    const closeMobileMenu = () => {
        document.body.classList.remove('sidebar-open');
        menuToggle?.setAttribute('aria-expanded', 'false');
    };
  const activate = (target, selectedItem) => {
    navItems.forEach(nav => nav.classList.remove('active'));
    sections.forEach(section => section.classList.remove('active'));
    selectedItem?.classList.add('active');
        const selectedGroup = selectedItem?.closest('.nav-group[data-sidebar-group]');
        if (selectedGroup && !Object.prototype.hasOwnProperty.call(savedSidebarGroups, selectedGroup.dataset.sidebarGroup)) {
            selectedGroup.open = true;
        }
    const selectedSection = document.getElementById(target);
    if (selectedSection) selectedSection.classList.add('active');
    document.dispatchEvent(new CustomEvent('section:deactivate:ultimas-saidas'));
    if (target === 'ultimas-saidas') document.dispatchEvent(new CustomEvent('section:activate:ultimas-saidas'));
    closeMobileMenu();
  };
  navItems.forEach(item => item.addEventListener('click', () => activate(item.dataset.target, item)));
  document.querySelectorAll('[data-section-target]').forEach(item => item.addEventListener('click', event => {
    event.preventDefault();
    activate(item.dataset.sectionTarget, document.querySelector(`.nav-item[data-target="${item.dataset.sectionTarget}"]`));
  }));
    menuToggle?.addEventListener('click', () => {
    const isOpen = document.body.classList.toggle('sidebar-open');
        menuToggle.setAttribute('aria-expanded', String(isOpen));
  });
    document.querySelector('.sidebar-close')?.addEventListener('click', closeMobileMenu);
    document.querySelector('.sidebar-scrim')?.addEventListener('click', closeMobileMenu);
    const initialSection = <?php echo json_encode($sectionInicial, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    activate(initialSection, document.querySelector(`.nav-item[data-target="${initialSection}"]`));
})();
</script>
</main>
</body>
</html>
