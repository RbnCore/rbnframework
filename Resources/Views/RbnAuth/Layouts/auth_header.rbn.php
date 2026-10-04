<!-- {{ $auth_name }} v{{ $auth_version }} - Professional Identity Service - {{ $author }} -->
<!DOCTYPE html>
<html lang="tr" data-theme="dark">

<head>
    <?= $seoHtml ?? '' ?>

    <!-- Dynamic Styles (Libraries & Context Assets) -->
    <?= $headerAssets ?? '' ?>
    <style>
        .rbn-auth-body {
            background-color: var(--rbn-slate-950, #020617) !important;
            background-image: 
                radial-gradient(circle at 50% 20%, rgba(245, 158, 11, 0.16) 0%, rgba(245, 158, 11, 0.02) 45%, transparent 75%),
                linear-gradient(rgba(255, 255, 255, 0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.035) 1px, transparent 1px) !important;
            background-size: 100% 100%, 32px 32px, 32px 32px !important;
            color: var(--rbn-slate-100, #f1f5f9) !important;
        }
        .rbn-auth-brand-pill {
            background: rgba(22, 29, 44, 0.75) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: var(--rbn-radius-pill, 9999px) !important;
            padding: 8px 24px !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4) !important;
            backdrop-filter: blur(16px) !important;
            -webkit-backdrop-filter: blur(16px) !important;
            margin-bottom: 24px !important;
        }
        .rbn-auth-stage-card {
            width: 100% !important;
            max-width: 420px !important;
            background: rgba(20, 28, 45, 0.88) !important;
            backdrop-filter: blur(24px) !important;
            -webkit-backdrop-filter: blur(24px) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 24px !important;
            padding: 28px 32px !important;
            box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.65), 0 0 50px rgba(245, 158, 11, 0.03) !important;
            margin: 0 auto !important;
        }
        .rbn-auth-nav-pill {
            background: rgba(12, 17, 28, 0.8) !important;
            border: 1px solid rgba(255, 255, 255, 0.06) !important;
            border-radius: 14px !important;
            padding: 5px !important;
            margin-bottom: 28px !important;
        }
        .rbn-auth-nav-link {
            color: var(--rbn-slate-400, #94a3b8) !important;
            font-size: 0.9rem !important;
            font-weight: 600 !important;
            border-radius: 10px !important;
            padding: 10px !important;
            text-decoration: none !important;
            transition: all 0.2s ease !important;
        }
        .rbn-auth-nav-link.active {
            background: linear-gradient(135deg, var(--rbn-amber-500, #f59e0b), var(--rbn-amber-600, #d97706)) !important;
            color: var(--rbn-white, #ffffff) !important;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4) !important;
        }
        .rbn-auth-social {
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(255, 255, 255, 0.09) !important;
            color: var(--rbn-slate-200, #e2e8f0) !important;
            border-radius: 14px !important;
            padding: 12px !important;
            font-weight: 600 !important;
            transition: all 0.2s ease !important;
        }
        .rbn-auth-social:hover {
            background: rgba(255, 255, 255, 0.08) !important;
            border-color: rgba(255, 255, 255, 0.18) !important;
            color: var(--rbn-white, #ffffff) !important;
            transform: translateY(-1px) !important;
        }
        .rbn-auth-divider {
            margin: 28px 0 !important;
        }
        .rbn-auth-divider-line {
            flex: 1 !important;
            height: 1px !important;
            background: rgba(255, 255, 255, 0.09) !important;
        }
        .rbn-auth-divider-text {
            font-size: 0.725rem !important;
            font-weight: 700 !important;
            letter-spacing: 0.08em !important;
            text-transform: uppercase !important;
            color: var(--rbn-slate-500, #64748b) !important;
            white-space: nowrap !important;
        }
        .rbn-auth-input-wrap {
            background: rgba(16, 23, 37, 0.85) !important;
            border: 1px solid rgba(255, 255, 255, 0.09) !important;
            border-radius: 14px !important;
            padding: 8px 16px 10px 16px !important;
            transition: all 0.2s ease !important;
            position: relative !important;
        }
        .rbn-auth-input-wrap:focus-within {
            border-color: var(--rbn-amber-500, #f59e0b) !important;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.18) !important;
            background: rgba(16, 23, 37, 0.98) !important;
        }
        .rbn-auth-input-wrap label {
            font-size: 0.75rem !important;
            font-weight: 700 !important;
            letter-spacing: 0.06em !important;
            color: var(--rbn-amber-500, #f59e0b) !important;
            display: flex !important;
            align-items: center !important;
            gap: 6px !important;
            line-height: 1.2 !important;
            margin: 0 !important;
        }
        .rbn-auth-input-wrap input {
            background: transparent !important;
            background-color: transparent !important;
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
            color: var(--rbn-white, #ffffff) !important;
            font-size: 0.95rem !important;
            font-weight: 500 !important;
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
            height: 24px !important;
            line-height: 24px !important;
        }
        .rbn-auth-input-wrap input:-webkit-autofill,
        .rbn-auth-input-wrap input:-webkit-autofill:hover, 
        .rbn-auth-input-wrap input:-webkit-autofill:focus,
        .rbn-auth-input-wrap input:-webkit-autofill:active {
            -webkit-text-fill-color: var(--rbn-white, #ffffff) !important;
            -webkit-box-shadow: 0 0 0px 1000px rgba(16, 23, 37, 1) inset !important;
            box-shadow: 0 0 0px 1000px rgba(16, 23, 37, 1) inset !important;
            transition: background-color 5000s ease-in-out 0s !important;
        }
        .rbn-auth-input-wrap input::placeholder {
            color: var(--rbn-slate-500, #64748b) !important;
        }
        .rbn-auth-btn-submit {
            background: linear-gradient(135deg, var(--rbn-amber-500, #f59e0b), var(--rbn-amber-600, #d97706)) !important;
            color: var(--rbn-white, #ffffff) !important;
            font-weight: 700 !important;
            border-radius: 14px !important;
            height: 52px !important;
            box-shadow: 0 8px 24px rgba(245, 158, 11, 0.4) !important;
        }
        .rbn-auth-btn-submit:hover {
            transform: translateY(-1px) !important;
            box-shadow: 0 12px 28px rgba(245, 158, 11, 0.5) !important;
            color: var(--rbn-white, #ffffff) !important;
        }
        /* Dark Checkbox Styling */
        .rbn-auth-checkbox {
            appearance: none !important;
            -webkit-appearance: none !important;
            width: 18px !important;
            height: 18px !important;
            background: rgba(16, 23, 37, 0.85) !important;
            border: 1px solid rgba(255, 255, 255, 0.18) !important;
            border-radius: 5px !important;
            cursor: pointer !important;
            display: inline-grid !important;
            place-content: center !important;
            margin: 0 !important;
        }
        .rbn-auth-checkbox:checked {
            background: var(--rbn-amber-500, #f59e0b) !important;
            border-color: var(--rbn-amber-500, #f59e0b) !important;
        }
        .rbn-auth-checkbox:checked::before {
            content: "✓" !important;
            font-size: 11px !important;
            color: var(--rbn-white, #ffffff) !important;
            font-weight: 900 !important;
        }
        /* Footer & Back Link Classes */
        .rbn-auth-back-wrap {
            margin-top: 24px !important;
            padding-top: 24px !important;
            border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
            text-align: center !important;
        }
        .rbn-auth-back-link {
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            font-size: 0.875rem !important;
            color: var(--rbn-slate-400, #94a3b8) !important;
            text-decoration: none !important;
            transition: color 0.15s ease !important;
        }
        .rbn-auth-back-link:hover {
            color: var(--rbn-white, #ffffff) !important;
        }
        .rbn-auth-features-bar {
            gap: 24px !important;
            font-size: 0.8rem !important;
            color: rgba(255, 255, 255, 0.6) !important;
            margin-top: 24px !important;
            padding-top: 8px !important;
            max-width: 850px !important;
        }
        .rbn-auth-feature-item {
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            letter-spacing: 0.01em !important;
        }
        .rbn-auth-eye-icon {
            position: absolute !important;
            right: 14px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            width: 28px !important;
            height: 28px !important;
            background: transparent !important;
            border: none !important;
            outline: none !important;
            padding: 0 !important;
            color: var(--rbn-teal, #0d9488) !important;
            cursor: pointer !important;
            z-index: 5 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 1.15rem !important;
        }
        .rbn-auth-brand-icon {
            width: 28px !important;
            height: 28px !important;
            background: rgba(245, 158, 11, 0.15) !important;
            color: var(--rbn-amber-500, #f59e0b) !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: var(--rbn-radius-pill, 9999px) !important;
        }
        .rbn-auth-brand-badge {
            font-size: 0.725rem !important;
            padding: 2px 8px !important;
            border-radius: var(--rbn-radius-pill, 9999px) !important;
            background: rgba(245, 158, 11, 0.12) !important;
            color: var(--rbn-amber-500, #f59e0b) !important;
            font-weight: 600 !important;
        }
        .rbn-auth-lock-avatar {
            width: 76px !important;
            height: 76px !important;
            background: rgba(245, 158, 11, 0.15) !important;
            color: var(--rbn-amber-500, #f59e0b) !important;
            border: 2px solid rgba(245, 158, 11, 0.3) !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: var(--rbn-radius-pill, 9999px) !important;
        }
        .rbn-auth-otp-input {
            height: 52px !important;
            background: rgba(16, 23, 37, 0.85) !important;
            border: 1px solid rgba(255, 255, 255, 0.09) !important;
            color: var(--rbn-white, #ffffff) !important;
            border-radius: 12px !important;
        }
        .rbn-auth-title {
            font-weight: 800 !important;
            letter-spacing: -0.02em !important;
            color: var(--rbn-white, #ffffff) !important;
            margin: 0 !important;
        }

        /* 📱 Responsive Mobile Breakpoints */
        @media (max-width: 576px) {
            .rbn-auth-stage-card {
                padding: 24px 18px !important;
                border-radius: 20px !important;
                margin-bottom: 16px !important;
            }
            .rbn-auth-brand-pill {
                padding: 6px 18px !important;
                margin-bottom: 16px !important;
            }
            .rbn-auth-title {
                font-size: 1.5rem !important;
            }
            .rbn-auth-features-bar {
                flex-direction: column !important;
                gap: 10px !important;
                margin-top: 16px !important;
            }
            .rbn-auth-otp-input {
                height: 46px !important;
                font-size: 1.25rem !important;
            }
        }
    </style>
</head>

<body class="rbn-auth-body d-flex flex-column min-vh-100 position-relative overflow-x-hidden">

<div class="container my-auto py-3 position-relative d-flex flex-column align-items-center justify-content-center" style="z-index: 2;">
    <div class="w-100 d-flex flex-column align-items-center">