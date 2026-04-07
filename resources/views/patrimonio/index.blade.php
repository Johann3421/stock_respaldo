@extends('layouts.admin')

@section('title', 'Patrimonio - Sistema de Inventario')
@section('page-title', 'Gestión de Patrimonio')

@section('styles')
<style>
    /* Blueprint Background Pattern - Optimized */
    .patrimonio-container {
        position: relative;
        width: 100%;
        height: 750px;
        background-color: #1a2332;
        background-image:
            repeating-linear-gradient(0deg, rgba(200,200,200,0.02) 0px, transparent 1px, transparent 40px, rgba(200,200,200,0.02) 41px),
            repeating-linear-gradient(90deg, rgba(200,200,200,0.02) 0px, transparent 1px, transparent 40px, rgba(200,200,200,0.02) 41px);
        border-radius: 20px;
        overflow: hidden;
        box-shadow:
            0 20px 60px rgba(0,0,0,0.6),
            inset 0 0 80px rgba(100,181,246,0.05);
        border: 2px solid rgba(100,150,200,0.2);
    }

    /* Animated blueprint effect - Reduced */
    .patrimonio-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 200%;
        height: 100%;
        background: linear-gradient(90deg,
            transparent 0%,
            rgba(100,181,246,0.08) 50%,
            transparent 100%);
        animation: blueprint-scan 10s linear infinite;
        will-change: left;
    }

    @keyframes blueprint-scan {
        0% { left: -100%; }
        100% { left: 100%; }
    }

    @media (prefers-reduced-motion: reduce) {
        .patrimonio-container::before {
            animation: none;
            opacity: 0.5;
        }
    }

    .floor-controls {
        position: absolute;
        top: 25px;
        right: 25px;
        z-index: 1000;
        display: flex;
        gap: 15px;
        flex-direction: column;
        align-items: flex-end;
    }

    /* Resumen de Área Cerrada */
    .area-summary-panel {
        background: rgba(16, 185, 129, 0.15);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border: 2px solid rgba(16, 185, 129, 0.5);
        border-radius: 10px;
        padding: 16px 20px;
        box-shadow: 0 8px 24px rgba(16, 185, 129, 0.2);
        margin-bottom: 10px;
        display: none;
        min-width: 280px;
    }

    .area-summary-panel.active {
        display: block;
        animation: slideInRight 0.3s ease-out;
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .summary-title {
        color: #10b981;
        font-size: 0.85rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .summary-title i {
        color: #34d399;
    }

    .summary-total {
        font-size: 1.4rem;
        font-weight: 800;
        color: #a7f3d0;
        margin-bottom: 8px;
        display: flex;
        align-items: baseline;
        gap: 4px;
    }

    .summary-total-label {
        font-size: 0.8rem;
        color: #6ee7b7;
        font-weight: 600;
    }

    .summary-user {
        font-size: 0.9rem;
        color: #d1fae5;
        border-top: 1px solid rgba(16, 185, 129, 0.3);
        padding-top: 8px;
        margin-top: 8px;
    }

    .summary-user-label {
        display: block;
        font-size: 0.75rem;
        color: #a7f3d0;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin-bottom: 3px;
    }

    .summary-user-name {
        font-weight: 700;
        color: #10b981;
    }

    .floor-btn {
        background: rgba(26, 35, 50, 0.9);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border: 2px solid rgba(100,150,200,0.4);
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(0,0,0,0.4);
        color: #a0c4ff;
        font-size: 0.95rem;
        letter-spacing: 0.3px;
        position: relative;
    }

    .floor-btn.active {
        background: rgba(100,181,246,0.2);
        color: #64b5f6;
        border-color: rgba(100,181,246,0.6);
        transform: translateX(-3px);
        box-shadow: 0 4px 15px rgba(100,181,246,0.3);
    }

    .floor-btn:hover:not(.active) {
        background: rgba(40,50,70,0.9);
        border-color: rgba(100,150,200,0.6);
        transform: translateX(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.5);
    }

    /* Help tooltip - Optimized and Expandable */
    .help-tooltip {
        position: absolute;
        top: 25px;
        left: 25px;
        background: rgba(26, 35, 50, 0.98);
        border: 2px solid rgba(100,150,200,0.4);
        padding: 12px 16px;
        border-radius: 8px;
        color: #a0c4ff;
        font-size: 0.95rem;
        z-index: 1001;
        box-shadow: 0 4px 15px rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        cursor: pointer;
        transition: all 0.3s ease;
        height: 20px;
        min-width: 50px;
        font-weight: 500;
        overflow: hidden;
    }

    .help-tooltip:hover {
        background: rgba(26, 35, 50, 0.99);
        border-color: rgba(100, 181, 246, 0.6);
        box-shadow: 0 6px 20px rgba(0,0,0,0.6), 0 0 20px rgba(100,181,246,0.2);
        transform: translateY(-2px);
        max-width: 380px;
        height: auto;
    }

    .help-tooltip i {
        font-size: 1.3rem;
        flex-shrink: 0;
        transition: transform 0.3s;
        color: #64b5f6;
    }

    .help-tooltip .help-text {
        display: flex;
        flex-direction: column;
        gap: 4px;
        opacity: 0;
        transition: opacity 0.3s ease;
        width: 0;
        overflow: hidden;
    }

    .help-tooltip:hover .help-text {
        opacity: 1;
        width: auto;
    }

    .help-tooltip strong {
        color: #64b5f6;
        display: block;
    }

    .help-tooltip:hover i {
        transform: rotate(15deg);
        color: #fff;
    }

    .floor-container {
        position: absolute;
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 100px 40px 40px;
        opacity: 0;
        transform: translateY(50px) rotateX(-10deg);
        transition: all 0.6s ease;
        pointer-events: none;
    }

    .floor-container.active {
        opacity: 1;
        transform: translateY(0) rotateX(0deg);
        pointer-events: auto;
    }

    .floor-grid {
        display: grid;
        gap: 20px;
        width: 100%;
        max-width: 1100px;
        perspective: 1000px;
    }

    /* Piso 1: Plano estilo tienda con área de ventas grande */
    .floor-grid-piso1 {
        grid-template-columns: 2fr 1fr;
        grid-template-rows: 1fr;
        height: 400px;
    }

    .floor-grid-piso1 .area-card:first-child {
        grid-column: 1 / 2;
    }

    /* Piso 2: Layout Pentagonal + Centro + Ensamblado */
    .floor-grid-piso2 {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        grid-template-rows: auto auto auto auto;
        gap: 10px;
        width: 100%;
        max-width: 1200px;
    }

    /* PENTÁGONO - Gerencia Arriba (Centro-Superior) */
    .area-gerencia {
        grid-column: 2 / 4;
        grid-row: 1 / 2;
        min-height: 100px;
    }

    /* PENTÁGONO - Administración Lado Izquierdo */
    .area-administracion {
        grid-column: 1 / 2;
        grid-row: 2 / 3;
        min-height: 100px;
    }

    /* PENTÁGONO - Contaduría Lado Derecho */
    .area-contaduria {
        grid-column: 4 / 5;
        grid-row: 2 / 3;
        min-height: 100px;
    }

    /* CENTRO - Sala de Reuniones (núcleo pentagonal) */
    .area-sala-reuniones {
        grid-column: 2 / 4;
        grid-row: 2 / 3;
        z-index: 10;
        min-height: 100px;
    }

    /* PENTÁGONO - Diseño Base Izquierda */
    .area-diseno {
        grid-column: 1 / 3;
        grid-row: 3 / 4;
        min-height: 100px;
    }

    /* PENTÁGONO - Sistemas Base Derecha */
    .area-sistemas {
        grid-column: 3 / 5;
        grid-row: 3 / 4;
        min-height: 100px;
    }

    /* ENSAMBLADO - Fila Completa Inferior */
    .area-ensamblado {
        grid-column: 1 / 5;
        grid-row: 4 / 5;
        min-height: 80px;
    }

    .area-card {
        background: rgba(26, 35, 50, 0.6);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 2px solid rgba(100,150,200,0.3);
        border-radius: 12px;
        padding: 0;
        cursor: pointer;
        transition: all 0.4s ease;
        box-shadow: 0 8px 25px rgba(0,0,0,0.5);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-end;
        position: relative;
        overflow: hidden;
        height: 100%;
    }

    .area-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background:
            repeating-linear-gradient(0deg, transparent 0px, rgba(100,181,246,0.05) 1px, transparent 2px, transparent 20px),
            repeating-linear-gradient(90deg, transparent 0px, rgba(100,181,246,0.05) 1px, transparent 2px, transparent 20px);
        opacity: 0;
        transition: opacity 0.5s;
        z-index: 1;
        pointer-events: none;
    }

    .area-card:hover::before {
        opacity: 1;
    }

    .area-bg {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center center;
        opacity: 0.5;
        transition: all 0.6s ease;
        filter: brightness(0.9);
    }

    .area-card:hover .area-bg {
        transform: scale(1.08);
        opacity: 0.7;
        filter: brightness(1.1);
    }

    .area-content {
        position: relative;
        z-index: 2;
        padding: 15px;
        text-align: center;
        width: 100%;
        background: linear-gradient(to top,
            rgba(26, 35, 50, 0.95) 0%,
            rgba(26, 35, 50, 0.6) 60%,
            transparent 100%);
        backdrop-filter: blur(6px);
        border-top: 1px solid rgba(100,150,200,0.2);
    }

    .area-icon {
        font-size: 2.2rem;
        margin-bottom: 8px;
        color: #64b5f6;
        filter: drop-shadow(0 0 10px rgba(100,181,246,0.5));
        transition: all 0.4s ease;
    }

    /* Efecto de paredes - Optimized */
    .area-card::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        border: 2px solid rgba(100,150,200,0.3);
        border-radius: 12px;
        pointer-events: none;
        transition: all 0.3s;
        z-index: 3;
    }

    .area-card:hover::after {
        border-color: rgba(100,181,246,0.6);
        box-shadow: inset 0 0 20px rgba(100,181,246,0.15);
    }

    .area-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 12px 35px rgba(0,0,0,0.6);
        border-color: rgba(100,181,246,0.5);
    }

    .area-card:hover .area-icon {
        transform: scale(1.1);
        transition: transform 0.4s;
    }

    .area-name {
        font-size: 1.1rem;
        font-weight: 700;
        color: #64b5f6;
        margin-bottom: 6px;
        text-shadow: 0 0 8px rgba(100,181,246,0.4);
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    .area-count {
        font-size: 0.75rem;
        color: #d0d0d0;
        background: rgba(100,181,246,0.15);
        padding: 4px 12px;
        border-radius: 20px;
        border: 1px solid rgba(100,150,200,0.3);
        font-weight: 500;
    }

    /* Modal Excel - Optimized */
    .excel-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(26, 35, 50, 0.95);
        backdrop-filter: blur(8px);
        z-index: 2000;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    .excel-modal.active {
        display: flex;
    }

    .excel-content {
        background: rgba(26, 35, 50, 0.98);
        border-radius: 12px;
        width: 100%;
        max-width: 1400px;
        max-height: 90vh;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0,0,0,0.7);
        border: 2px solid rgba(100,150,200,0.3);
    }

    .excel-header {
        background: rgba(26, 35, 50, 0.95);
        color: #64b5f6;
        padding: 20px 30px;
        border-radius: 12px 12px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.4);
        border-bottom: 2px solid rgba(100,150,200,0.3);
    }

    .excel-header h3 {
        margin: 0;
        font-size: 1.6rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        color: #a0c4ff;
    }

    .close-modal {
        background: rgba(100,150,200,0.15);
        color: #64b5f6;
        border: 2px solid rgba(100,150,200,0.3);
        width: 38px;
        height: 38px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 1.6rem;
        font-weight: bold;
        line-height: 34px;
        text-align: center;
        transition: all 0.2s ease;
        box-shadow: 0 0 10px rgba(0,0,0,0.3);
    }

    .close-modal:hover {
        background: #ef4444;
        border-color: #ef4444;
        color: #fff;
        transform: rotate(90deg) scale(1.1);
    }

    .excel-body {
        padding: 35px;
        max-height: calc(92vh - 100px);
        overflow-y: auto;
        background: rgba(15,28,63,0.3);
    }

    /* Custom scrollbar */
    .excel-body::-webkit-scrollbar {
        width: 10px;
    }

    .excel-body::-webkit-scrollbar-track {
        background: rgba(100,181,246,0.1);
        border-radius: 10px;
    }

    .excel-body::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, rgba(100,181,246,0.5) 0%, rgba(66,165,245,0.5) 100%);
        border-radius: 10px;
    }

    .excel-body::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, rgba(100,181,246,0.8) 0%, rgba(66,165,245,0.8) 100%);
    }

    .excel-table {
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
        margin-top: 20px;
        border: 1px solid rgba(100,150,200,0.2);
        background: rgba(26, 35, 50, 0.4);
    }

    .excel-table th {
        background: rgba(26, 35, 50, 0.9);
        color: #a0c4ff;
        padding: 12px 10px;
        text-align: left;
        font-weight: 600;
        font-size: 0.9rem;
        border: 1px solid rgba(100,150,200,0.2);
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .excel-table td {
        padding: 10px;
        border: 1px solid rgba(100,150,200,0.15);
        background: rgba(26, 35, 50, 0.3);
        color: #d0d0d0;
    }

    .excel-table tbody tr:hover {
        background: rgba(100,181,246,0.1);
    }

    .excel-table tbody tr:nth-child(even) {
        background: rgba(26, 35, 50, 0.5);
    }

    .excel-table input,
    .excel-table select {
        width: 100%;
        padding: 6px 8px;
        border: 1px solid rgba(100,150,200,0.3);
        border-radius: 4px;
        font-size: 0.9rem;
        background: rgba(26, 35, 50, 0.6);
        color: #d0d0d0;
        transition: border 0.2s;
    }

    .excel-table input:focus,
    .excel-table select:focus {
        outline: none;
        border-color: rgba(100,181,246,0.6);
        box-shadow: 0 0 0 2px rgba(100,181,246,0.1);
        background: rgba(26, 35, 50, 0.8);
    }

    .btn-add-row {
        background: rgba(16,185,129,0.8);
        color: white;
        border: 2px solid rgba(16,185,129,0.5);
        padding: 10px 22px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(16,185,129,0.3);
    }

    .btn-add-row:hover {
        background: rgba(5,150,105,0.95);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16,185,129,0.4);
    }

    .btn-close-inventory {
        background: rgba(239, 68, 68, 0.8);
        color: white;
        border: 2px solid rgba(239, 68, 68, 0.5);
        padding: 10px 22px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        display: flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
    }

    .btn-close-inventory:hover {
        background: rgba(220, 38, 38, 0.95);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
    }

    .btn-close-inventory:disabled {
        background: rgba(107, 114, 128, 0.6);
        border-color: rgba(107, 114, 128, 0.4);
        cursor: not-allowed;
        box-shadow: none;
    }

    .btn-close-inventory:disabled:hover {
        transform: none;
    }

    /* Badge de cierre en esquina de área */
    .area-closed-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 100;
        width: 40px;
        height: 40px;
        background: rgba(16, 185, 129, 0.9);
        border: 2px solid rgba(34, 197, 94, 0.7);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        font-size: 1.2rem;
        color: #fff;
    }

    .area-closed-badge:hover {
        background: rgba(16, 185, 129, 1);
        transform: scale(1.15);
        box-shadow: 0 6px 16px rgba(16, 185, 129, 0.6);
    }

    /* Modal de cierre por área */
    .area-closure-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(26, 35, 50, 0.95);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        z-index: 3000;
        justify-content: center;
        align-items: center;
        padding: 20px;
        animation: fadeIn 0.3s ease-out;
    }

    .area-closure-modal.active {
        display: flex;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    .area-closure-card {
        background: rgba(26, 35, 50, 0.98);
        border-radius: 16px;
        padding: 40px;
        max-width: 500px;
        width: 100%;
        border: 2px solid rgba(16, 185, 129, 0.5);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.7), 0 0 40px rgba(16, 185, 129, 0.3);
        text-align: center;
        animation: slideUpScale 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes slideUpScale {
        from {
            opacity: 0;
            transform: translateY(40px) scale(0.9);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .closure-header {
        margin-bottom: 30px;
    }

    .closure-icon {
        font-size: 3.5rem;
        color: #10b981;
        margin-bottom: 15px;
        display: block;
    }

    .closure-title {
        font-size: 1.8rem;
        font-weight: 800;
        color: #a7f3d0;
        margin-bottom: 8px;
        letter-spacing: 0.5px;
    }

    .closure-subtitle {
        font-size: 0.95rem;
        color: #6ee7b7;
        font-weight: 500;
    }

    .closure-body {
        border-top: 2px solid rgba(16, 185, 129, 0.3);
        border-bottom: 2px solid rgba(16, 185, 129, 0.3);
        padding: 25px 0;
        margin: 20px 0;
    }

    .closure-item {
        margin-bottom: 20px;
    }

    .closure-item:last-child {
        margin-bottom: 0;
    }

    .closure-label {
        font-size: 0.85rem;
        color: #6ee7b7;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
        display: block;
    }

    .closure-value {
        font-size: 1.5rem;
        color: #d1fae5;
        font-weight: 700;
    }

    .closure-value-large {
        font-size: 2.2rem;
        color: #10b981;
        font-weight: 900;
    }

    .closure-footer {
        margin-top: 25px;
    }

    .closure-date {
        font-size: 0.9rem;
        color: #a7f3d0;
        margin-bottom: 12px;
    }

    .btn-close-modal-closure {
        background: rgba(16, 185, 129, 0.8);
        color: white;
        border: 2px solid rgba(16, 185, 129, 0.5);
        padding: 12px 30px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }

    .btn-close-modal-closure:hover {
        background: rgba(16, 185, 129, 0.95);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    }
        background: rgba(100,181,246,0.8);
        color: white;
        border: 2px solid rgba(100,181,246,0.5);
        padding: 12px 35px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 1rem;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(100,181,246,0.3);
        width: 100%;
        margin-top: 20px;
    }

    .btn-save-all:hover {
        background: rgba(100,181,246,0.95);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(100,181,246,0.4);
    }

    .btn-delete-row {
        background: rgba(239,68,68,0.8);
        color: white;
        border: 2px solid rgba(239,68,68,0.5);
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .btn-delete-row:hover {
        background: rgba(220,38,38,0.95);
        transform: scale(1.05);
        box-shadow: 0 2px 8px rgba(239,68,68,0.4);
    }

    .excel-dropdown {
        position: relative;
        display: inline-block;
    }

    .excel-dropdown-btn {
        background: rgba(100, 181, 246, 0.8);
        color: white;
        border: 2px solid rgba(100, 181, 246, 0.5);
        padding: 10px 22px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(100, 181, 246, 0.3);
    }

    .excel-dropdown-btn:hover {
        background: rgba(100, 181, 246, 0.95);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(100, 181, 246, 0.4);
    }

    .excel-dropdown-menu {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        background: rgba(40, 80, 140, 0.98);
        border: 2px solid rgba(100, 181, 246, 0.5);
        border-radius: 6px;
        margin-top: 5px;
        min-width: 200px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.6), 0 0 20px rgba(100, 181, 246, 0.3);
        z-index: 1000;
    }

    .excel-dropdown-menu.active {
        display: block;
        animation: slideDown 0.2s ease-out;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-5px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .excel-dropdown-menu a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        color: #e0f0ff;
        text-decoration: none;
        border-bottom: 1px solid rgba(100, 181, 246, 0.3);
        transition: all 0.2s ease;
        cursor: pointer;
        font-weight: 500;
    }

    .excel-dropdown-menu a:last-child {
        border-bottom: none;
    }

    .excel-dropdown-menu a:hover {
        background: rgba(100, 181, 246, 0.25);
        color: #ffffff;
        padding-left: 20px;
    }

    .excel-dropdown-menu i {
        width: 20px;
        text-align: center;
        color: #64b5f6;
    }

    .floor-label {
        position: absolute;
        top: 25px;
        left: 50%;
        transform: translateX(-50%);
        background: linear-gradient(135deg, rgba(15,28,63,0.95) 0%, rgba(25,45,85,0.95) 100%);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 2px solid rgba(100,181,246,0.5);
        color: #64b5f6;
        padding: 12px 30px;
        border-radius: 8px;
        font-weight: 800;
        font-size: 1.3rem;
        box-shadow: 0 8px 32px rgba(0,0,0,0.5), 0 0 20px rgba(100,181,246,0.3);
        letter-spacing: 1px;
        text-shadow: 0 0 15px rgba(100,181,246,0.8);
        z-index: 999;
    }

    @media (max-width: 768px) {
        .floor-grid-piso1,
        .floor-grid-piso2 {
            grid-template-columns: 1fr;
            grid-template-rows: auto;
            height: auto;
        }

        .patrimonio-container {
            height: auto;
            min-height: 600px;
        }

        .area-card {
            min-height: 220px;
        }

        .floor-controls {
            top: 15px;
            right: 15px;
            flex-direction: row;
            gap: 8px;
        }

        .floor-btn {
            padding: 10px 20px;
            font-size: 0.9rem;
        }

        .excel-table {
            font-size: 0.85rem;
        }

        .excel-table th,
        .excel-table td {
            padding: 8px 6px;
        }

        .floor-grid-piso1 .area-card:last-child {
            display: none; /* Ocultar el info label en móvil */
        }

        .area-name {
            font-size: 1.4rem;
        }

        .area-icon {
            font-size: 2.5rem;
        }
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Visualización 3D de Pisos -->
        <div class="patrimonio-container">
            <!-- Help Tooltip -->
            <div class="help-tooltip">
                <i class="fas fa-info-circle"></i>
                <span class="help-text"><strong>Instrucciones:</strong> Haz clic en un área para gestionar su patrimonio</span>
            </div>

            <div class="floor-label" id="floorLabel">Piso 1 - Planta Baja</div>

            <div class="floor-controls">
                <!-- Panel de Resumen de Área Cerrada -->
                <div class="area-summary-panel" id="areaSummaryPanel">
                    <div class="summary-title">
                        <i class="fas fa-check-circle"></i>
                        Inventario Cerrado
                    </div>
                    <div class="summary-total">
                        <span class="summary-total-label">Total:</span>
                        <span id="summaryTotalValue">$0.00</span>
                    </div>
                    <div class="summary-user">
                        <span class="summary-user-label">Cierre por:</span>
                        <span class="summary-user-name" id="summaryUserName">—</span>
                    </div>
                </div>

                <button class="floor-btn active" data-floor="1" onclick="switchFloor(1)">
                    <i class="fas fa-store"></i> Piso 1
                </button>
                <button class="floor-btn" data-floor="2" onclick="switchFloor(2)">
                    <i class="fas fa-building"></i> Piso 2
                </button>
            </div>

            <!-- Piso 1: Ventas -->
            <div class="floor-container active" id="floor1">
                <div class="floor-grid floor-grid-piso1">
                    <!-- Área Principal: Ventas -->
                    <div class="area-card" data-area="Ventas" data-piso="1" onclick="openAreaModal(1, 'Ventas')">
                        <div class="area-closed-badge" id="badge-ventas" onclick="event.stopPropagation(); showAreaClosureInfo('Ventas', 1)" style="display: none;">
                            <i class="fas fa-check"></i>
                        </div>
                        <img src="https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=600&h=600&fit=crop&q=75" alt="Ventas" class="area-bg" loading="lazy">
                        <div class="area-content">
                            <div class="area-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="area-name">Área de Ventas</div>
                            <div class="area-count" id="count-ventas">0 artículos</div>
                        </div>
                    </div>

                    <!-- Info Label -->
                    <div class="area-card" style="background: rgba(255,255,255,0.1); cursor: default; pointer-events: none;">
                        <div class="area-content" style="height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; background: transparent;">
                            <div class="area-icon" style="font-size: 2.5rem; opacity: 0.6;">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div style="color: white; font-size: 1rem; opacity: 0.8; text-align: center; padding: 0 20px;">
                                Planta Baja<br>
                                <small style="font-size: 0.85rem;">Área comercial</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Piso 2: Pentágono + Sala Reuniones + Ensamblado -->
            <div class="floor-container" id="floor2">
                <div class="floor-grid floor-grid-piso2">
                    <!-- GERENCIA - Arriba (Cabeza del Pentágono) -->
                    <div class="area-card area-gerencia" data-area="Gerencia" data-piso="2" onclick="openAreaModal(2, 'Gerencia')">
                        <div class="area-closed-badge" id="badge-gerencia" onclick="event.stopPropagation(); showAreaClosureInfo('Gerencia', 2)" style="display: none;">
                            <i class="fas fa-check"></i>
                        </div>
                        <img src="https://images.unsplash.com/photo-1497366754035-f200968a6e72?w=600&h=600&fit=crop&q=75" alt="Gerencia" class="area-bg" loading="lazy">
                        <div class="area-content">
                            <div class="area-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="area-name">Gerencia</div>
                            <div class="area-count" id="count-gerencia">0 artículos</div>
                        </div>
                    </div>

                    <!-- ADMINISTRACIÓN - Lado Izquierdo del Pentágono -->
                    <div class="area-card area-administracion" data-area="Administración" data-piso="2" onclick="openAreaModal(2, 'Administración')">
                        <div class="area-closed-badge" id="badge-administracion" onclick="event.stopPropagation(); showAreaClosureInfo('Administración', 2)" style="display: none;">
                            <i class="fas fa-check"></i>
                        </div>
                        <img src="https://images.unsplash.com/photo-1552664730-d307ca884978?w=600&h=600&fit=crop&q=75" alt="Administración" class="area-bg" loading="lazy">
                        <div class="area-content">
                            <div class="area-icon">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <div class="area-name">Administración</div>
                            <div class="area-count" id="count-administracion">0 artículos</div>
                        </div>
                    </div>

                    <!-- CONTADURÍA - Lado Derecho del Pentágono -->
                    <div class="area-card area-contaduria" data-area="Contaduría" data-piso="2" onclick="openAreaModal(2, 'Contaduría')">
                        <div class="area-closed-badge" id="badge-contaduria" onclick="event.stopPropagation(); showAreaClosureInfo('Contaduría', 2)" style="display: none;">
                            <i class="fas fa-check"></i>
                        </div>
                        <img src="https://images.unsplash.com/photo-1554224154-26032ffc0d07?w=600&h=600&fit=crop&q=75" alt="Contaduría" class="area-bg" loading="lazy">
                        <div class="area-content">
                            <div class="area-icon">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                            <div class="area-name">Contabilidad</div>
                            <div class="area-count" id="count-contaduria">0 artículos</div>
                        </div>
                    </div>

                    <!-- SALA DE REUNIONES - Centro (Núcleo del Pentágono) -->
                    <div class="area-card area-sala-reuniones" data-area="Sala de Reuniones" data-piso="2" onclick="openAreaModal(2, 'Sala de Reuniones')">
                        <div class="area-closed-badge" id="badge-sala-de-reuniones" onclick="event.stopPropagation(); showAreaClosureInfo('Sala de Reuniones', 2)" style="display: none;">
                            <i class="fas fa-check"></i>
                        </div>
                        <img src="https://images.unsplash.com/photo-1552664730-d307ca884978?w=600&h=600&fit=crop&q=75" alt="Sala de Reuniones" class="area-bg" loading="lazy">
                        <div class="area-content">
                            <div class="area-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="area-name">Sala de Reuniones</div>
                            <div class="area-count" id="count-sala-de-reuniones">0 artículos</div>
                        </div>
                    </div>

                    <!-- DISEÑO - Base Izquierda del Pentágono -->
                    <div class="area-card area-diseno" data-area="Diseño" data-piso="2" onclick="openAreaModal(2, 'Diseño')">
                        <div class="area-closed-badge" id="badge-diseno" onclick="event.stopPropagation(); showAreaClosureInfo('Diseño', 2)" style="display: none;">
                            <i class="fas fa-check"></i>
                        </div>
                        <img src="https://images.unsplash.com/photo-1626785774573-4b799315345d?w=600&h=600&fit=crop&q=75" alt="Diseño" class="area-bg" loading="lazy">
                        <div class="area-content">
                            <div class="area-icon">
                                <i class="fas fa-palette"></i>
                            </div>
                            <div class="area-name">Diseño Gráfico</div>
                            <div class="area-count" id="count-diseno">0 artículos</div>
                        </div>
                    </div>

                    <!-- SISTEMAS - Base Derecha del Pentágono -->
                    <div class="area-card area-sistemas" data-area="Sistemas" data-piso="2" onclick="openAreaModal(2, 'Sistemas')">
                        <div class="area-closed-badge" id="badge-sistemas" onclick="event.stopPropagation(); showAreaClosureInfo('Sistemas', 2)" style="display: none;">
                            <i class="fas fa-check"></i>
                        </div>
                        <img src="https://images.unsplash.com/photo-1488590528505-98d2b5aba04b?w=600&h=600&fit=crop&q=75" alt="Sistemas" class="area-bg" loading="lazy">
                        <div class="area-content">
                            <div class="area-icon">
                                <i class="fas fa-laptop-code"></i>
                            </div>
                            <div class="area-name">Sistemas e IT</div>
                            <div class="area-count" id="count-sistemas">0 artículos</div>
                        </div>
                    </div>

                    <!-- ENSAMBLADO - Fila Completa Inferior -->
                    <div class="area-card area-ensamblado" data-area="Ensamblado" data-piso="2" onclick="openAreaModal(2, 'Ensamblado')">
                        <div class="area-closed-badge" id="badge-ensamblado" onclick="event.stopPropagation(); showAreaClosureInfo('Ensamblado', 2)" style="display: none;">
                            <i class="fas fa-check"></i>
                        </div>
                        <img src="https://images.unsplash.com/photo-1621905167918-48416bd8575a?w=1200&h=300&fit=crop&q=75" alt="Ensamblado" class="area-bg" loading="lazy">
                        <div class="area-content">
                            <div class="area-icon">
                                <i class="fas fa-hammer"></i>
                            </div>
                            <div class="area-name">Área de Ensamblado</div>
                            <div class="area-count" id="count-ensamblado">0 artículos</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Cierre por Área -->
<div class="area-closure-modal" id="areaClosureModal">
    <div class="area-closure-card">
        <div class="closure-header">
            <i class="closure-icon fas fa-check-circle"></i>
            <h2 class="closure-title" id="closureAreaName">Ventas</h2>
            <p class="closure-subtitle">Inventario Cerrado</p>
        </div>
        <div class="closure-body">
            <div class="closure-item">
                <span class="closure-label">Total de Valores</span>
                <span class="closure-value-large" id="closureAreaTotal">$0.00</span>
            </div>
            <div class="closure-item">
                <span class="closure-label">Cierre Realizado por</span>
                <span class="closure-value" id="closureAreaUser">—</span>
            </div>
        </div>
        <div class="closure-footer">
            <p class="closure-date" id="closureAreaDate">Fecha: —</p>
            <button class="btn-close-modal-closure" onclick="closeAreaClosureModal()">
                <i class="fas fa-times"></i> Cerrar
            </button>
        </div>
    </div>
</div>

<!-- Modal tipo Excel -->
<div class="excel-modal" id="excelModal">
    <div class="excel-content">
        <div class="excel-header">
            <h3 id="modalTitle">Gestión de Artículos</h3>
            <div style="display: flex; align-items: center; gap: 20px;">
                <div id="areaClosureInfo" style="display: none; text-align: right; font-size: 0.9rem;">
                    <div style="color: #64b5f6; font-weight: 600;">Inventario Cerrado</div>
                    <div id="closureDate" style="color: #a0c4ff; font-size: 0.85rem;"></div>
                    <div id="closureTotal" style="color: #10b981; font-size: 0.85rem; font-weight: 600;"></div>
                </div>
                <button id="closeInventoryBtn" class="btn-close-inventory" onclick="closeAreaInventory()" title="Cerrar inventario del área">
                    <i class="fas fa-lock"></i> Cerrar Inventario
                </button>
                <button class="close-modal" onclick="closeModal()">×</button>
            </div>
        </div>
        <div class="excel-body">
            <div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
                <button class="btn-add-row" onclick="addNewRow()">
                    <i class="fas fa-plus"></i> Agregar Artículo
                </button>
                <div class="excel-dropdown">
                    <button class="excel-dropdown-btn" onclick="toggleExcelMenu()">
                        <i class="fas fa-file-excel"></i> Opciones Excel <i class="fas fa-chevron-down" style="margin-left: 5px;"></i>
                    </button>
                    <div class="excel-dropdown-menu" id="excelDropdownMenu">
                        <a onclick="exportToExcel()">
                            <i class="fas fa-download"></i> Exportar Excel
                        </a>
                        <a onclick="importExcelFile()">
                            <i class="fas fa-upload"></i> Importar Excel
                        </a>
                        <a onclick="downloadTemplate()">
                            <i class="fas fa-file-contract"></i> Plantilla
                        </a>
                    </div>
                </div>
            </div>
            <input type="file" id="excelFileInput" accept=".xlsx,.xls" style="display: none;" onchange="handleExcelImport(event)">

            <table class="excel-table" id="patrimonioTable">
                <thead>
                    <tr>
                        <th>Código Patrimonial</th>
                        <th>Descripción</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Serie</th>
                        <th>Estado</th>
                        <th>Valor</th>
                        <th>Fecha Adq.</th>
                        <th>Responsable</th>
                        <th>Observaciones</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody id="patrimonioTableBody">
                    <!-- Rows will be added dynamically -->
                </tbody>
            </table>

            <button class="btn-save-all" onclick="saveAllItems()">
                <i class="fas fa-save"></i> Guardar Todos los Cambios
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
let displayedFloor = 1;  // Piso mostrado actualmente en la UI
let currentPiso = 1;     // Piso del área en edición en la modal
let currentArea = '';
let itemsData = @json($items ?? []);

// Inicializar anime.js
document.addEventListener('DOMContentLoaded', function() {
    updateItemCounts();
    animateFloorOnLoad();
});

function animateFloorOnLoad() {
    anime({
        targets: '.area-card',
        scale: [0, 1],
        opacity: [0, 1],
        translateY: [50, 0],
        rotateY: [-90, 0],
        delay: anime.stagger(100),
        duration: 800,
        easing: 'easeOutElastic(1, .8)'
    });
}

function switchFloor(floor) {
    if (displayedFloor === floor) return;

    const oldFloor = document.getElementById('floor' + displayedFloor);
    const newFloor = document.getElementById('floor' + floor);
    const oldBtn = document.querySelector('.floor-btn[data-floor="' + displayedFloor + '"]');
    const newBtn = document.querySelector('.floor-btn[data-floor="' + floor + '"]');
    const label = document.getElementById('floorLabel');

    // Detener animaciones previas
    anime.remove(oldFloor);
    anime.remove(newFloor);

    // Actualizar botones
    if (oldBtn) oldBtn.classList.remove('active');
    if (newBtn) newBtn.classList.add('active');

    // Transición simplificada para mejor rendimiento
    anime({
        targets: oldFloor,
        opacity: [1, 0],
        duration: 300,
        easing: 'easeInQuad',
        complete: function() {
            oldFloor.classList.remove('active');
            newFloor.style.display = 'flex';
            newFloor.classList.add('active');

            // Entrada del nuevo piso
            anime({
                targets: newFloor,
                opacity: [0, 1],
                duration: 300,
                easing: 'easeOutQuad'
            });

            // Animar tarjetas con stagger reducido
            anime({
                targets: '#floor' + floor + ' .area-card',
                opacity: [0, 1],
                translateY: [20, 0],
                delay: anime.stagger(60, {start: 80}),
                duration: 350,
                easing: 'easeOutQuad'
            });
        }
    });

    // Actualizar label
    label.textContent = floor === 1 ? 'Piso 1 - Planta Baja' : 'Piso 2 - Pentágono de Áreas';
    anime({
        targets: label,
        opacity: [0.5, 1],
        duration: 300,
        easing: 'easeOutQuad'
    });

    displayedFloor = floor;
}

/**
 * Toggle del menú desplegable de Excel
 */
function toggleExcelMenu() {
    const menu = document.getElementById('excelDropdownMenu');
    menu.classList.toggle('active');

    // Cerrar menú si se hace clic fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.excel-dropdown')) {
            menu.classList.remove('active');
        }
    });
}

/**
 * Verificar si XLSX está disponible
 */
function checkXLSXAvailable() {
    if (typeof XLSX === 'undefined') {
        alert('La librería de Excel aún se está cargando. Por favor intenta de nuevo en un momento.');
        return false;
    }
    return true;
}

function closeModal() {
    const modal = document.getElementById('excelModal');
    const modalContent = document.querySelector('.excel-content');

    anime.remove(modalContent);

    anime({
        targets: modalContent,
        opacity: [1, 0],
        duration: 200,
        easing: 'easeInQuad',
        complete: function() {
            modal.classList.remove('active');
            modal.style.display = 'none';
        }
    });
}

function openAreaModal(piso, area) {
    currentPiso = piso;
    currentArea = area;

    const modal = document.getElementById('excelModal');
    const modalContent = document.querySelector('.excel-content');

    document.getElementById('modalTitle').textContent = area + ' - Piso ' + piso;
    modal.style.display = 'flex';
    setTimeout(function() {
        modal.classList.add('active');
    }, 10);

    // Cargar items del área
    loadAreaItems(piso, area);

    // Cargar resumen del área (cierre y totales)
    loadAreaSummary(piso, area);

    // Animar modal de forma simplificada
    anime.remove(modalContent);
    anime({
        targets: modalContent,
        opacity: [0, 1],
        duration: 250,
        easing: 'easeOutQuad'
    });
}

function loadAreaItems(piso, area) {
    fetch(`/patrimonio/${piso}/${encodeURIComponent(area)}`)
        .then(response => {
            if (!response.ok) {
                console.warn(`Respuesta no OK: ${response.status}`);
                return [];
            }
            return response.json();
        })
        .then(items => {
            const tbody = document.getElementById('patrimonioTableBody');
            tbody.innerHTML = '';

            if (Array.isArray(items)) {
                items.forEach(item => {
                    addRowWithData(item);
                });

                if (items.length === 0) {
                    addNewRow();
                }
            } else {
                addNewRow();
            }
        })
        .catch(error => {
            console.error('Error loading items:', error);
            const tbody = document.getElementById('patrimonioTableBody');
            tbody.innerHTML = '';
            addNewRow();
        });
}

function addNewRow() {
    addRowWithData(null);
}

/**
 * Nota: Los códigos QR se generan automáticamente en el servidor
 * Formato: GA01260 + Prefijo del área (2 letras) + Número secuencial (3 dígitos)
 * Ejemplos: GA01260ST001, GA01260CT002, GA01260GR003
 * El campo es readonly y no se puede editar
 */

function addRowWithData(item) {
    const tbody = document.getElementById('patrimonioTableBody');
    const row = tbody.insertRow();
    row.dataset.itemId = item?.id || '';

    // Formatear la fecha correctamente para input type="date"
    let fechaFormato = '';
    if (item?.fecha_adquisicion) {
        const fecha = new Date(item.fecha_adquisicion);
        const year = fecha.getFullYear();
        const month = String(fecha.getMonth() + 1).padStart(2, '0');
        const day = String(fecha.getDate()).padStart(2, '0');
        fechaFormato = `${year}-${month}-${day}`;
    }

    row.innerHTML = `
        <td><input type="text" name="codigo_patrimonial" value="${item?.codigo_patrimonial || ''}" readonly style="background-color: rgba(100,150,200,0.1); cursor: not-allowed;"></td>
        <td><input type="text" name="descripcion" value="${item?.descripcion || ''}" required></td>
        <td><input type="text" name="marca" value="${item?.marca || ''}"></td>
        <td><input type="text" name="modelo" value="${item?.modelo || ''}"></td>
        <td><input type="text" name="serie" value="${item?.serie || ''}"></td>
        <td>
            <select name="estado" required>
                <option value="Operativo" ${item?.estado === 'Operativo' ? 'selected' : ''}>Operativo</option>
                <option value="Inoperativo" ${item?.estado === 'Inoperativo' ? 'selected' : ''}>Inoperativo</option>
                <option value="En reparación" ${item?.estado === 'En reparación' ? 'selected' : ''}>En reparación</option>
                <option value="De baja" ${item?.estado === 'De baja' ? 'selected' : ''}>De baja</option>
            </select>
        </td>
        <td><input type="number" step="0.01" name="valor_adquisicion" value="${item?.valor_adquisicion || ''}"></td>
        <td><input type="date" name="fecha_adquisicion" value="${fechaFormato}"></td>
        <td><input type="text" name="responsable" value="${item?.responsable || ''}"></td>
        <td><input type="text" name="observaciones" value="${item?.observaciones || ''}"></td>
        <td>
            <button class="btn-delete-row" onclick="deleteRow(this)">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;

    // Animar nueva fila de forma optimizada
    anime({
        targets: row,
        opacity: [0, 1],
        duration: 300,
        easing: 'easeOutQuad'
    });
}

function deleteRow(btn) {
    const row = btn.closest('tr');
    const itemId = row.dataset.itemId;

    if (itemId) {
        if (!confirm('¿Estás seguro de eliminar este artículo?')) return;

        fetch(`/patrimonio/${itemId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                area: currentArea
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                anime({
                    targets: row,
                    opacity: [1, 0],
                    duration: 250,
                    easing: 'easeInQuad',
                    complete: () => {
                        row.remove();
                        updateItemCounts();
                    }
                });
            } else {
                alert('Error al eliminar: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Hubo un error al eliminar el artículo');
        });
    } else {
        row.remove();
    }
}

function saveAllItems() {
    const rows = document.querySelectorAll('#patrimonioTableBody tr');
    const promises = [];

    rows.forEach(row => {
        const itemId = row.dataset.itemId;

        // Obtener y formatear la fecha correctamente
        let fechaAquisicion = row.querySelector('[name="fecha_adquisicion"]').value;

        const formData = {
            area: currentArea,
            piso: currentPiso,
            descripcion: row.querySelector('[name="descripcion"]').value,
            marca: row.querySelector('[name="marca"]').value,
            modelo: row.querySelector('[name="modelo"]').value,
            serie: row.querySelector('[name="serie"]').value,
            estado: row.querySelector('[name="estado"]').value,
            valor_adquisicion: row.querySelector('[name="valor_adquisicion"]').value,
            fecha_adquisicion: fechaAquisicion, // Ya está en formato Y-m-d del input type="date"
            responsable: row.querySelector('[name="responsable"]').value,
            observaciones: row.querySelector('[name="observaciones"]').value
        };

        const url = itemId ? `/patrimonio/${itemId}` : '/patrimonio';
        const method = itemId ? 'PUT' : 'POST';

        promises.push(
            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            })
        );
    });

    Promise.all(promises)
        .then(responses => Promise.all(responses.map(r => {
            if (!r.ok) {
                console.warn(`Respuesta no OK: ${r.status}`);
            }
            return r.json().catch(e => ({ success: false, message: 'Error parsing response' }));
        })))
        .then(results => {
            const allSuccess = results.every(r => r.success);
            if (allSuccess) {
                alert('Todos los artículos se guardaron correctamente');
                loadAreaItems(currentPiso, currentArea);
                updateItemCounts();
            } else {
                const errors = results.filter(r => !r.success).map(r => r.message).join(', ');
                alert('Error al guardar: ' + errors);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Hubo un error al guardar algunos artículos');
        });
}

function updateItemCounts() {
    // Cargar todos los contadores usando una función consistente
    const areasCount = {
        1: ['Ventas'],
        2: ['Contaduría', 'Gerencia', 'Diseño', 'Sistemas', 'Administración', 'Sala de Reuniones', 'Ensamblado']
    };

    for (const [piso, areas] of Object.entries(areasCount)) {
        areas.forEach(area => {
            fetch(`/patrimonio/${piso}/${encodeURIComponent(area)}`)
                .then(response => {
                    if (!response.ok) {
                        console.warn(`Respuesta no OK para ${area}: ${response.status}`);
                        return [];
                    }
                    return response.json();
                })
                .then(items => {
                    // Crear ID consistente
                    const id = `count-${area.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/\s+/g, '-')}`;
                    const el = document.getElementById(id);
                    if (el) {
                        const count = Array.isArray(items) ? items.length : 0;
                        el.textContent = `${count} artículos`;
                    }
                })
                .catch(error => console.error(`Error al cargar ${area}:`, error));
        });
    }

    // Actualizar badges
    updateAreaBadges();
}

// Cerrar modal con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('excelModal').classList.contains('active')) {
        closeModal();
    }
});

/**
 * Exportar datos del área a Excel
 */
function exportToExcel() {
    if (!checkXLSXAvailable()) return;

    document.getElementById('excelDropdownMenu').classList.remove('active');

    const rows = document.querySelectorAll('#patrimonioTableBody tr');
    if (rows.length === 0) {
        alert('No hay artículos para exportar');
        return;
    }

    // Crear libro de Excel con datos
    const wb = XLSX.utils.book_new();
    const data = [];

    // Encabezados
    data.push([
        'Código Patrimonial',
        'Descripción',
        'Marca',
        'Modelo',
        'Serie',
        'Estado',
        'Valor Adquisición',
        'Fecha Adquisición',
        'Responsable',
        'Observaciones'
    ]);

    // Datos de filas
    rows.forEach(row => {
        const codigo = row.querySelector('[name="codigo_patrimonial"]').value;
        const descripcion = row.querySelector('[name="descripcion"]').value;
        const marca = row.querySelector('[name="marca"]').value;
        const modelo = row.querySelector('[name="modelo"]').value;
        const serie = row.querySelector('[name="serie"]').value;
        const estado = row.querySelector('[name="estado"]').value;
        const valor = row.querySelector('[name="valor_adquisicion"]').value;
        const fecha = row.querySelector('[name="fecha_adquisicion"]').value;
        const responsable = row.querySelector('[name="responsable"]').value;
        const observaciones = row.querySelector('[name="observaciones"]').value;

        data.push([codigo, descripcion, marca, modelo, serie, estado, valor, fecha, responsable, observaciones]);
    });

    const ws = XLSX.utils.aoa_to_sheet(data);
    ws['!cols'] = [
        { wch: 18 }, // Código
        { wch: 30 }, // Descripción
        { wch: 15 }, // Marca
        { wch: 15 }, // Modelo
        { wch: 15 }, // Serie
        { wch: 15 }, // Estado
        { wch: 15 }, // Valor
        { wch: 15 }, // Fecha
        { wch: 15 }, // Responsable
        { wch: 25 }  // Observaciones
    ];

    XLSX.utils.book_append_sheet(wb, ws, currentArea);
    const filename = `${currentArea}_${new Date().toISOString().split('T')[0]}.xlsx`;
    XLSX.writeFile(wb, filename);
}

/**
 * Descargar plantilla de Excel para importar
 */
function downloadTemplate() {
    if (!checkXLSXAvailable()) return;

    document.getElementById('excelDropdownMenu').classList.remove('active');
    const data = [];

    // Encabezados
    data.push([
        'Código Patrimonial',
        'Descripción',
        'Marca',
        'Modelo',
        'Serie',
        'Estado',
        'Valor Adquisición',
        'Fecha Adquisición',
        'Responsable',
        'Observaciones'
    ]);

    // Fila de ejemplo
    data.push([
        'SE GENERA AUTOMÁTICO',
        'Computadora',
        'Dell',
        'OptiPlex',
        'ABC123',
        'Operativo',
        '800.00',
        '2026-01-15',
        'Juan Pérez',
        'Ejemplo de artículo'
    ]);

    const ws = XLSX.utils.aoa_to_sheet(data);
    ws['!cols'] = [
        { wch: 18 }, // Código
        { wch: 30 }, // Descripción
        { wch: 15 }, // Marca
        { wch: 15 }, // Modelo
        { wch: 15 }, // Serie
        { wch: 15 }, // Estado
        { wch: 15 }, // Valor
        { wch: 15 }, // Fecha
        { wch: 15 }, // Responsable
        { wch: 25 }  // Observaciones
    ];

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Plantilla');
    const filename = `Plantilla_${currentArea}_${new Date().toISOString().split('T')[0]}.xlsx`;
    XLSX.writeFile(wb, filename);
}

/**
 * Importar datos desde Excel
 */
function importExcelFile() {
    document.getElementById('excelDropdownMenu').classList.remove('active');
    document.getElementById('excelFileInput').click();
}

/**
 * Manejar la importación de archivo Excel
 */
function handleExcelImport(event) {
    if (!checkXLSXAvailable()) return;

    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const worksheet = workbook.Sheets[workbook.SheetNames[0]];
            const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

            if (jsonData.length < 2) {
                alert('El archivo está vacío o no tiene datos válidos');
                return;
            }

            // Limpiar tabla actual
            const tbody = document.getElementById('patrimonioTableBody');
            tbody.innerHTML = '';

            // Importar datos (saltando encabezado)
            for (let i = 1; i < jsonData.length; i++) {
                const row = jsonData[i];

                // Crear objeto de item
                const item = {
                    codigo_patrimonial: row[0] || '',
                    descripcion: row[1] || '',
                    marca: row[2] || '',
                    modelo: row[3] || '',
                    serie: row[4] || '',
                    estado: row[5] || 'Operativo',
                    valor_adquisicion: row[6] || '',
                    fecha_adquisicion: row[7] || '',
                    responsable: row[8] || '',
                    observaciones: row[9] || ''
                };

                addRowWithData(item);
            }

            alert(`Se importaron ${jsonData.length - 1} artículos correctamente`);
        } catch (error) {
            console.error('Error importando Excel:', error);
            alert('Error al importar el archivo. Asegúrate de que sea un Excel válido.');
        }
    };

    reader.readAsArrayBuffer(file);

    // Limpiar input
    event.target.value = '';
}

/**
 * Cargar resumen del área (estado de cierre y total de valores)
 */
function loadAreaSummary(piso, area) {
    fetch(`/patrimonio/${piso}/${encodeURIComponent(area)}/summary`)
        .then(response => {
            if (!response.ok) {
                console.warn(`Respuesta no OK: ${response.status}`);
                return { total_value: 0, closed_at: null, closed_by_user: null, item_count: 0 };
            }
            return response.json();
        })
        .then(data => {
            const infoDiv = document.getElementById('areaClosureInfo');
            const closeBtn = document.getElementById('closeInventoryBtn');
            const dateDiv = document.getElementById('closureDate');
            const totalDiv = document.getElementById('closureTotal');

            // Panel de resumen en esquina superior derecha
            const summaryPanel = document.getElementById('areaSummaryPanel');
            const summaryTotalValue = document.getElementById('summaryTotalValue');
            const summaryUserName = document.getElementById('summaryUserName');

            if (data.closed_at) {
                // El área ya está cerrada
                infoDiv.style.display = 'block';
                dateDiv.textContent = 'Fecha: ' + formatClosureDate(data.closed_at);
                totalDiv.textContent = 'Total: $' + data.total_value;
                closeBtn.disabled = true;
                closeBtn.style.opacity = '0.5';
                closeBtn.title = 'Este inventario ya fue cerrado';

                // Actualizar panel de resumen
                summaryPanel.classList.add('active');
                summaryTotalValue.textContent = '$' + data.total_value;
                summaryUserName.textContent = data.closed_by_user || '—';
            } else {
                // El área aún está abierta
                infoDiv.style.display = 'none';
                closeBtn.disabled = false;
                closeBtn.style.opacity = '1';
                closeBtn.title = 'Cerrar inventario del área';

                // Ocultar panel de resumen
                summaryPanel.classList.remove('active');
            }
        })
        .catch(error => {
            console.error('Error loading summary:', error);
            document.getElementById('areaClosureInfo').style.display = 'none';
            document.getElementById('areaSummaryPanel').classList.remove('active');
        });
}

/**
 * Cerrar el inventario del área actual
 */
function closeAreaInventory() {
    const btn = document.getElementById('closeInventoryBtn');

    if (btn.disabled) {
        alert('Este inventario ya fue cerrado');
        return;
    }

    if (!confirm(`¿Deseas cerrar el inventario del área "${currentArea}"? Esta acción no se puede deshacer.`)) {
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cerrando...';

    fetch(`/patrimonio/${currentPiso}/${encodeURIComponent(currentArea)}/close`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Mostrar información del cierre
            const infoDiv = document.getElementById('areaClosureInfo');
            const dateDiv = document.getElementById('closureDate');
            const totalDiv = document.getElementById('closureTotal');

            // Panel de resumen en esquina superior derecha
            const summaryPanel = document.getElementById('areaSummaryPanel');
            const summaryTotalValue = document.getElementById('summaryTotalValue');
            const summaryUserName = document.getElementById('summaryUserName');

            infoDiv.style.display = 'block';
            dateDiv.textContent = 'Fecha: ' + data.closed_at;
            totalDiv.textContent = 'Total: $' + data.total_value;

            // Actualizar panel de resumen
            summaryPanel.classList.add('active');
            summaryTotalValue.textContent = '$' + data.total_value;
            summaryUserName.textContent = data.closed_by_user || '—';

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-lock"></i> Cerrado';
            btn.style.opacity = '0.5';

            alert(`Inventario cerrado correctamente.\n\nTotal de artículos: ${data.item_count}\nValor total: $${data.total_value}\nFecha de cierre: ${data.closed_at}\nCierre por: ${data.closed_by_user}`);

            // Actualizar contadores
            updateItemCounts();
        } else {
            alert('Error al cerrar: ' + (data.message || 'Error desconocido'));
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-lock"></i> Cerrar Inventario';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Hubo un error al cerrar el inventario');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-lock"></i> Cerrar Inventario';
    });
}

/**
 * Formatear fecha de cierre para mostrar
 */
function formatClosureDate(dateString) {
    const date = new Date(dateString);
    const options = {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    };
    return date.toLocaleString('es-CO', options);
}

/**
 * Mostrar información de cierre de un área desde el badge
 */
function showAreaClosureInfo(area, piso) {
    fetch(`/patrimonio/${piso}/${encodeURIComponent(area)}/summary`)
        .then(response => {
            if (!response.ok) {
                throw new Error('No se pudo cargar la información');
            }
            return response.json();
        })
        .then(data => {
            if (data.closed_at) {
                // Actualizar modal con información del área
                document.getElementById('closureAreaName').textContent = area;
                document.getElementById('closureAreaTotal').textContent = '$' + data.total_value;
                document.getElementById('closureAreaUser').textContent = data.closed_by_user || '—';
                document.getElementById('closureAreaDate').textContent = 'Fecha: ' + formatClosureDate(data.closed_at);

                // Mostrar modal
                document.getElementById('areaClosureModal').classList.add('active');
            } else {
                alert('Este área aún no está cerrada');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar la información del cierre');
        });
}

/**
 * Cerrar el modal de información de cierre
 */
function closeAreaClosureModal() {
    const modal = document.getElementById('areaClosureModal');
    modal.classList.remove('active');
}

/**
 * Cerrar modal al hacer clic fuera
 */
document.addEventListener('click', function(e) {
    const modal = document.getElementById('areaClosureModal');
    if (e.target === modal) {
        closeAreaClosureModal();
    }
});

/**
 * Actualizar badges cuando se cargan los resúmenes
 */
function updateAreaBadges() {
    const areasCount = {
        1: ['Ventas'],
        2: ['Contaduría', 'Gerencia', 'Diseño', 'Sistemas', 'Administración', 'Sala de Reuniones', 'Ensamblado']
    };

    for (const [piso, areas] of Object.entries(areasCount)) {
        areas.forEach(area => {
            fetch(`/patrimonio/${piso}/${encodeURIComponent(area)}/summary`)
                .then(response => {
                    if (!response.ok) return { closed_at: null };
                    return response.json();
                })
                .then(data => {
                    // Crear ID consistente para el badge
                    const badgeId = `badge-${area.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/\s+/g, '-')}`;
                    const badge = document.getElementById(badgeId);

                    if (badge) {
                        if (data.closed_at) {
                            badge.style.display = 'flex';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                })
                .catch(error => console.error(`Error checking ${area}:`, error));
        });
    }
}

// Actualizar badges al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    updateAreaBadges();
});

</script>
@endsection
