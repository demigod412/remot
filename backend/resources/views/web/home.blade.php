@extends('web.layouts.app')

@section('title', 'RemotioX – AI Training & Data Annotation Jobs')

@section('content')

{{-- Pass translated typewriter text to JS (static) --}}
@php
    $heroTyper = [
        "Earn by ",
        "labeling",
        ",\nevaluating AI,",
        " Shaping smarter models."
    ];
@endphp
<script>
    window._heroTyper = @json($heroTyper);
</script>

{{-- ── HERO ──────────────────────────────────────────────────── --}}
<section id="home" style="position:relative; overflow:hidden; padding:80px 40px 100px; background:#fff;">
    <div aria-hidden="true" style="position:absolute; inset:0; z-index:0; pointer-events:none;
         background: linear-gradient(135deg, #0b1120 0%, #1a2a4a 40%, #2f4b7a 100%);"></div>
    <div aria-hidden="true" style="position:absolute; inset:0; z-index:1; pointer-events:none;
         background: linear-gradient(100deg, rgba(11,17,32,0.92) 0%, rgba(26,42,74,0.78) 50%, rgba(47,75,122,0.4) 100%);"></div>

    <div style="position:relative; z-index:2; max-width:1280px; margin:0 auto; display:grid; grid-template-columns:1.1fr 1fr; gap:80px; align-items:center;" class="hero-grid">

        <div>
            <div class="anim-hero-badge" style="display:inline-flex; align-items:center; gap:8px; padding:5px 12px 5px 6px; border-radius:999px; background:rgba(255,255,255,0.08); backdrop-filter:blur(8px); border:1px solid rgba(255,255,255,0.15); font-size:12.5px; color:rgba(255,255,255,0.7); margin-bottom:28px;">
                <span style="background:#22c55e; color:#fff; padding:2px 8px; border-radius:999px; font-weight:600; font-size:11px;">HIRING</span>
                Remote AI projects · 1,200+ open tasks
            </div>
            <h1 id="hero-h1" style="font-size:clamp(42px,5.2vw,72px); line-height:1.08; font-weight:600; letter-spacing:-2.5px; margin:0 0 24px; color:#fff; min-height:4.4em;">
                <span id="hero-typed"></span><span class="typing-cursor">|</span>
            </h1>
            <p class="anim-hero-p" style="font-size:17px; color:rgba(255,255,255,0.8); line-height:1.55; max-width:480px; margin:0 0 36px;">
                RemotioX connects you with flexible, paid opportunities in AI training, data labeling, and prompt engineering. Join a global community contributing to smarter, safer artificial intelligence.
            </p>
            <div class="anim-hero-actions" style="display:flex; gap:12px; margin-bottom:40px; flex-wrap:wrap;">
                <a href="{{ route('membership.apply') }}" class="btn btn-primary" style="padding:12px 20px; font-size:14px; background:#3b82f6; border:none; color:#fff; border-radius:40px; font-weight:500;">
                    Get started
                    <svg width="14" height="14" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M3 9h12M10 4l5 5-5 5"/></svg>
                </a>
                <a href="{{ route('user.register') }}" class="btn btn-secondary" style="padding:12px 20px; font-size:14px; background:rgba(255,255,255,0.08); backdrop-filter:blur(6px); border:1px solid rgba(255,255,255,0.2); color:#fff; border-radius:40px; font-weight:500;">
                    Explore projects
                </a>
            </div>
            <div class="anim-hero-stats" style="display:flex; gap:36px; padding-top:28px; border-top:1px solid rgba(255,255,255,0.12); flex-wrap:wrap;">
                <div>
                    <div class="mono counter" data-target="50000" style="font-size:22px; font-weight:600; letter-spacing:-0.5px; color:#fff;">50,000</div>
                    <div style="font-size:12px; color:rgba(255,255,255,0.6); margin-top:2px;">tasks completed</div>
                </div>
                <div>
                    <div class="mono" style="font-size:22px; font-weight:600; letter-spacing:-0.5px; color:#f5d547;">$2.5M</div>
                    <div style="font-size:12px; color:rgba(255,255,255,0.6); margin-top:2px;">paid out to contributors</div>
                </div>
                <div>
                    <div class="mono counter" data-target="12000" style="font-size:22px; font-weight:600; letter-spacing:-0.5px; color:#fff;">12,000</div>
                    <div style="font-size:12px; color:rgba(255,255,255,0.6); margin-top:2px;">active contributors</div>
                </div>
            </div>
        </div>

        {{-- Floating task cards --}}
        <div style="position:relative; height:520px;" class="hero-cards">
            @php
                $tasks = [
                    ['Label 100 images – object detection', '$12', 'Data Labeling', '0px', '0px', '0s'],
                    ['Evaluate AI response quality (RLHF)', '$18', 'RLHF', '130px', '200px', '0.8s'],
                    ['Rewrite prompts for clarity & safety', '$15', 'Prompt Eng.', '280px', '10px', '1.6s'],
                    ['Code review for LLM fine-tuning', '$25', 'Coding Expert', '400px', '220px', '2.4s'],
                ];
            @endphp
            @foreach($tasks as [$title, $pay, $category, $top, $left, $delay])
            <div class="hero-float-card card" style="position:absolute; top:{{ $top }}; left:{{ $left }}; width:260px; padding:16px; background:rgba(255,255,255,0.1); backdrop-filter:blur(12px); border:1px solid rgba(255,255,255,0.12); border-radius:16px; box-shadow:0 20px 60px -20px rgba(0,0,0,0.4); animation-delay:{{ $delay }};">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <span class="chip chip-instant" style="font-size:10px; text-transform:uppercase; background:rgba(59,130,246,0.2); color:#60a5fa; padding:2px 8px; border-radius:20px;">⚡ AI</span>
                    <span class="mono" style="font-size:11px; color:rgba(255,255,255,0.5);">~20 min</span>
                </div>
                <div style="font-size:14px; font-weight:500; line-height:1.35; margin-bottom:14px; color:#fff;">{{ $title }}</div>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span class="mono" style="font-size:14px; font-weight:600; color:#22c55e;">{{ $pay }}</span>
                    <span style="font-size:11px; color:rgba(255,255,255,0.5);">{{ $category }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── WHY REMOTIOX ──────────────────────────────────────────── --}}
<section id="why" style="padding:100px 40px; border-top:1px solid rgba(0,0,0,0.06); background:#f8fafc;">
    <div style="max-width:1200px; margin:0 auto;">
        <div data-reveal style="text-align:center; margin-bottom:60px;">
            <div style="font-size:11px; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; color:#3b82f6; margin-bottom:12px;">Why RemotioX</div>
            <h2 style="font-size:clamp(28px,3.5vw,44px); font-weight:600; letter-spacing:-1.5px; margin:0; color:#0b1120;">Work that fits your life – and shapes the future.</h2>
        </div>
        <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:24px;" class="four-col">
            @foreach([
                ['🧠', 'Flexible hours', 'Choose tasks that fit your schedule – work 1 hour or 30 per week.'],
                ['🌍', 'Global community', 'Join 12,000+ contributors from 80+ countries, all building better AI.'],
                ['💰', 'Fair pay, fast', 'Transparent pricing, weekly payouts, and no hidden fees.'],
                ['🚀', 'Skill growth', 'Work on diverse projects and gain experience in the AI industry.'],
            ] as $idx => [$icon, $title, $desc])
            <div class="card reveal-card" data-reveal data-delay="{{ $idx * 80 }}" style="padding:24px; background:#fff; border-radius:20px; box-shadow:0 2px 12px rgba(0,0,0,0.04); border:1px solid rgba(0,0,0,0.04);">
                <div style="font-size:28px; margin-bottom:12px;">{{ $icon }}</div>
                <h3 style="font-size:18px; font-weight:600; margin:0 0 8px; color:#0b1120;">{{ $title }}</h3>
                <p style="font-size:13.5px; color:#475569; line-height:1.5; margin:0;">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── ABOUT REMOTIOX (mission & values) ────────────────────── --}}
<section id="about" style="padding:100px 40px; border-top:1px solid rgba(0,0,0,0.06); background:#fff;">
    <div style="max-width:1200px; margin:0 auto; display:grid; grid-template-columns:1fr 1fr; gap:60px; align-items:center;" class="about-grid">
        <div data-reveal>
            <div style="font-size:11px; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; color:#3b82f6; margin-bottom:12px;">About RemotioX</div>
            <h2 style="font-size:clamp(28px,3.5vw,44px); font-weight:600; letter-spacing:-1.5px; margin:0 0 20px; color:#0b1120; line-height:1.1;">
                Human intelligence meets AI innovation.
            </h2>
            <p style="font-size:16px; color:#475569; line-height:1.7; margin-bottom:20px;">
                RemotioX is a global platform that connects skilled professionals with flexible opportunities in AI training and data annotation. We partner with leading AI labs and enterprises to label data, evaluate model outputs, and fine‑tune large language models.
            </p>
            <p style="font-size:16px; color:#475569; line-height:1.7; margin-bottom:20px;">
                Our mission is to make AI development more inclusive, transparent, and human‑centric. By empowering contributors from diverse backgrounds, we help build AI systems that are accurate, safe, and representative of the world we live in.
            </p>
            <div style="display:flex; gap:16px; flex-wrap:wrap; margin-top:24px;">
                <span style="padding:4px 12px; background:#3b82f622; border-radius:20px; font-size:13px; color:#3b82f6;">🌱 Mission-driven</span>
                <span style="padding:4px 12px; background:#3b82f622; border-radius:20px; font-size:13px; color:#3b82f6;">🤝 Community-first</span>
                <span style="padding:4px 12px; background:#3b82f622; border-radius:20px; font-size:13px; color:#3b82f6;">🔬 Quality-obsessed</span>
            </div>
        </div>
        <div data-reveal data-delay="100" style="background:linear-gradient(135deg, #0b1120, #1a2a4a); border-radius:24px; padding:40px; color:#fff; position:relative; overflow:hidden;">
            <div style="position:absolute; top:-40px; right:-40px; font-size:120px; opacity:0.06; font-weight:700;">AI</div>
            <blockquote style="font-size:18px; line-height:1.6; margin:0; font-style:italic; color:rgba(255,255,255,0.9);">
                “The future of AI is built through human knowledge, collaboration, and continuous learning.”
            </blockquote>
            <div style="margin-top:24px; display:flex; align-items:center; gap:12px;">
                <div style="width:48px; height:48px; border-radius:50%; background:#3b82f6; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:20px;">R</div>
                <div>
                    <div style="font-weight:600; font-size:16px;">RemotioX Team</div>
                    <div style="font-size:13px; opacity:0.6;">Empowering AI contributors worldwide</div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── HOW IT WORKS ──────────────────────────────────────────── --}}
<section id="how" style="padding:100px 40px; border-top:1px solid rgba(0,0,0,0.06); background:#f8fafc;">
    <div style="max-width:1200px; margin:0 auto;">
        <div data-reveal style="display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:60px; flex-wrap:wrap; gap:20px;">
            <div>
                <div style="font-size:11px; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; color:#3b82f6; margin-bottom:12px;">How it works</div>
                <h2 style="font-size:clamp(28px,3.5vw,44px); font-weight:600; letter-spacing:-1.5px; margin:0; max-width:600px; color:#0b1120; line-height:1.1;">
                    Three steps to start earning with AI.
                </h2>
            </div>
            
        </div>
        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:24px;" class="three-col">
            @foreach([
                ['01', '📝', 'Sign up & verify', 'Create your profile, complete basic assessments, and get verified in minutes.'],
                ['02', '🎯', 'Choose projects', 'Browse tasks that match your skills – from data labeling to expert coding.'],
                ['03', '💎', 'Earn & grow', 'Complete tasks, get paid, and build your reputation for higher‑value projects.'],
            ] as $idx => [$num, $icon, $title, $desc])
            <div class="card reveal-card" data-reveal data-delay="{{ $idx * 120 }}" style="padding:28px; background:#fff; border-radius:20px; border:1px solid rgba(0,0,0,0.04); box-shadow:0 2px 8px rgba(0,0,0,0.02);">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:28px;">
                    <div style="width:44px; height:44px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:20px; background:#3b82f622; color:#3b82f6;">{{ $icon }}</div>
                    <span class="mono" style="font-size:11px; color:#94a3b8;">{{ $num }}</span>
                </div>
                <h3 style="font-size:22px; font-weight:600; margin:0 0 12px; letter-spacing:-0.4px; color:#0b1120;">{{ $title }}</h3>
                <p style="font-size:13.5px; color:#475569; line-height:1.5; margin:0;">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── PROJECT TYPES ──────────────────────────────────────────── --}}
<section id="projects" style="padding:100px 40px; border-top:1px solid rgba(0,0,0,0.06); background:#fff;">
    <div style="max-width:1200px; margin:0 auto;">
        <div data-reveal style="text-align:center; margin-bottom:60px;">
            <div style="font-size:11px; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; color:#3b82f6; margin-bottom:12px;">Project types</div>
            <h2 style="font-size:clamp(28px,3.5vw,44px); font-weight:600; letter-spacing:-1.5px; margin:0; color:#0b1120;">Diverse AI tasks for every skill level.</h2>
        </div>
        <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:18px;" class="four-col">
            @foreach([
                ['🖼️', 'Data Labeling', 'Annotate images, text, or audio for computer vision and NLP models.', '#3b82f6'],
                ['🤖', 'RLHF Evaluators', 'Evaluate AI responses for safety, helpfulness, and accuracy.', '#8b5cf6'],
                ['✍️', 'Prompt Engineering', 'Craft and refine prompts to improve LLM output quality.', '#f59e0b'],
                ['💻', 'Domain Experts', 'Apply coding, math, science, or language expertise to advanced AI training.', '#22c55e'],
            ] as $idx => [$icon, $title, $desc, $color])
            <div class="card reveal-card" data-reveal data-delay="{{ $idx * 80 }}" style="padding:24px; background:#f8fafc; border-radius:20px; border:1px solid rgba(0,0,0,0.04); box-shadow:0 2px 8px rgba(0,0,0,0.02);">
                <div style="font-size:32px; margin-bottom:10px;">{{ $icon }}</div>
                <h3 style="font-size:17px; font-weight:600; margin:0 0 6px; color:#0b1120;">{{ $title }}</h3>
                <p style="font-size:13px; color:#475569; line-height:1.5; margin:0;">{{ $desc }}</p>
                <div style="margin-top:12px; display:inline-block; padding:2px 10px; background:{{ $color }}22; color:{{ $color }}; border-radius:20px; font-size:11px; font-weight:500;">open</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── FEATURED PROJECTS (was Live strip) ─────────────────────── --}}
<section style="padding:80px 40px; border-top:1px solid rgba(0,0,0,0.06); background:#f8fafc;">
    <div style="max-width:1200px; margin:0 auto;">
        <div data-reveal style="display:flex; align-items:center; gap:12px; margin-bottom:28px;">
            <span style="display:inline-block; width:6px; height:6px; border-radius:50%; background:#22c55e; animation:pulse-urgent 1.6s infinite; flex-shrink:0;"></span>
            <span style="font-size:11px; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; color:#22c55e;">Featured projects</span>
            <div style="flex:1; height:1px; background:rgba(0,0,0,0.06);"></div>
            <span style="font-size:12.5px; color:#64748b;">1,200+ active tasks</span>
        </div>
        <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:14px;" class="four-col">
            @foreach([
                ['Identify 50 images of traffic signs', '$10', 'Data Labeling'],
                ['Evaluate a set of 20 chatbot responses', '$14', 'RLHF'],
                ['Rewrite 30 prompts for clarity', '$12', 'Prompt Eng.'],
                ['Debug a Python code snippet for LLM training', '$20', 'Coding Expert'],
            ] as $idx => [$title, $pay, $cat])
            <div class="card" data-reveal data-delay="{{ $idx * 80 }}" style="padding:14px; background:#fff; border-radius:16px; border:1px solid rgba(0,0,0,0.04); transition:transform .2s, box-shadow .2s; box-shadow:0 2px 8px rgba(0,0,0,0.03);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <span class="chip chip-instant" style="font-size:10px; text-transform:uppercase; background:#3b82f622; color:#3b82f6; padding:2px 8px; border-radius:20px;">⚡ AI</span>
                    <span style="font-size:11px; color:#94a3b8;">{{ $cat }}</span>
                </div>
                <div style="font-size:13.5px; font-weight:500; line-height:1.35; margin-bottom:12px; min-height:38px; color:#0b1120;">{{ $title }}</div>
                <div style="display:flex; justify-content:space-between; align-items:center; padding-top:10px; border-top:1px solid rgba(0,0,0,0.04);">
                    <span class="mono" style="font-size:14px; font-weight:600; color:#22c55e;">{{ $pay }}</span>
                    <span style="font-size:11px; font-weight:500; color:#3b82f6;">Apply →</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── WHY JOIN (Benefits for Contributors) ───────────────────── --}}
<section id="benefits" style="padding:100px 40px; border-top:1px solid rgba(0,0,0,0.06); background:#fff;">
    <div style="max-width:1200px; margin:0 auto;">
        <div data-reveal style="text-align:center; margin-bottom:60px;">
            <div style="font-size:11px; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; color:#3b82f6; margin-bottom:12px;">Why join</div>
            <h2 style="font-size:clamp(28px,3.5vw,44px); font-weight:600; letter-spacing:-1.5px; margin:0; color:#0b1120;">More than just a side gig.</h2>
        </div>
        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:24px;" class="three-col">
            @foreach([
                ['🌿', 'Work‑life balance', 'Set your own hours and work from anywhere. No commuting, no fixed schedules – you’re in control.'],
                ['📚', 'Learn new skills', 'Get hands‑on experience with cutting‑edge AI tools and workflows. Many contributors use RemotioX to pivot into AI careers.'],
                ['🤝', 'Supportive community', 'Join a global network of like‑minded individuals. Share tips, ask questions, and grow together.'],
                ['🏆', 'Recognition & growth', 'Top performers gain access to premium projects and mentorship opportunities. Build a portfolio that stands out.'],
                ['💵', 'Competitive pay', 'Earn above market rates for your skills. No fees, no hidden costs – what you see is what you get.'],
                ['🔒', 'Secure & transparent', 'We handle payments, disputes, and quality checks so you can focus on the work. Your data and earnings are always safe.'],
            ] as $idx => [$icon, $title, $desc])
            <div class="card reveal-card" data-reveal data-delay="{{ $idx * 80 }}" style="padding:24px; background:#f8fafc; border-radius:20px; border:1px solid rgba(0,0,0,0.04); display:flex; gap:14px; align-items:flex-start;">
                <div style="font-size:28px; flex-shrink:0;">{{ $icon }}</div>
                <div>
                    <h3 style="font-size:17px; font-weight:600; margin:0 0 6px; color:#0b1120;">{{ $title }}</h3>
                    <p style="font-size:13px; color:#475569; line-height:1.5; margin:0;">{{ $desc }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── QUALITY ASSURANCE ───────────────────────────────────────── --}}
<section id="quality" style="padding:100px 40px; border-top:1px solid rgba(0,0,0,0.06); background:#f8fafc;">
    <div style="max-width:1200px; margin:0 auto;">
        <div data-reveal style="display:grid; grid-template-columns:1fr 1fr; gap:60px; align-items:center;" class="quality-grid">
            <div>
                <div style="font-size:11px; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; color:#3b82f6; margin-bottom:12px;">Quality assurance</div>
                <h2 style="font-size:clamp(28px,3.5vw,44px); font-weight:600; letter-spacing:-1.5px; margin:0 0 20px; color:#0b1120; line-height:1.1;">
                    Trusted by leading AI labs.
                </h2>
                <p style="font-size:16px; color:#475569; line-height:1.7; margin-bottom:16px;">
                    Our quality framework ensures that every task meets the highest standards. We combine automated checks with human review to guarantee accuracy and fairness.
                </p>
                <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:12px;">
                    <li style="display:flex; gap:10px; align-items:flex-start; font-size:14px; color:#475569;">
                        <svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round"><path d="M4 10l3 3 9-9"/></svg>
                        <span><strong>Clear guidelines</strong> – every project comes with detailed instructions and examples.</span>
                    </li>
                    <li style="display:flex; gap:10px; align-items:flex-start; font-size:14px; color:#475569;">
                        <svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round"><path d="M4 10l3 3 9-9"/></svg>
                        <span><strong>Fair reviews</strong> – submissions are evaluated by trained moderators; you can appeal any rejection.</span>
                    </li>
                    <li style="display:flex; gap:10px; align-items:flex-start; font-size:14px; color:#475569;">
                        <svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round"><path d="M4 10l3 3 9-9"/></svg>
                        <span><strong>Continuous feedback</strong> – we share performance insights to help you improve and earn more.</span>
                    </li>
                    <li style="display:flex; gap:10px; align-items:flex-start; font-size:14px; color:#475569;">
                        <svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round"><path d="M4 10l3 3 9-9"/></svg>
                        <span><strong>98.7% approval rate</strong> – our contributors consistently deliver high‑quality work.</span>
                    </li>
                </ul>
            </div>
            <div data-reveal data-delay="100" style="background:#fff; border-radius:24px; padding:40px; border:1px solid rgba(0,0,0,0.04); box-shadow:0 4px 16px rgba(0,0,0,0.03);">
                <div style="display:flex; justify-content:space-between; margin-bottom:24px;">
                    <span style="font-size:14px; font-weight:600; color:#0b1120;">Quality metrics</span>
                    <span style="font-size:12px; color:#94a3b8;">Last 30 days</span>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div style="background:#f1f5f9; border-radius:12px; padding:16px; text-align:center;">
                        <div style="font-size:24px; font-weight:700; color:#22c55e;">98.7%</div>
                        <div style="font-size:12px; color:#64748b;">Approval rate</div>
                    </div>
                    <div style="background:#f1f5f9; border-radius:12px; padding:16px; text-align:center;">
                        <div style="font-size:24px; font-weight:700; color:#3b82f6;">4.2m</div>
                        <div style="font-size:12px; color:#64748b;">Avg. review time</div>
                    </div>
                    <div style="background:#f1f5f9; border-radius:12px; padding:16px; text-align:center;">
                        <div style="font-size:24px; font-weight:700; color:#f59e0b;">98%</div>
                        <div style="font-size:12px; color:#64748b;">Contributor satisfaction</div>
                    </div>
                    <div style="background:#f1f5f9; border-radius:12px; padding:16px; text-align:center;">
                        <div style="font-size:24px; font-weight:700; color:#8b5cf6;">12k+</div>
                        <div style="font-size:12px; color:#64748b;">Tasks reviewed</div>
                    </div>
                </div>
                <div style="margin-top:20px; padding:16px; background:#f8fafc; border-radius:12px; text-align:center; font-size:13px; color:#475569;">
                    <span style="font-weight:600;">🔍</span> Every task is checked by our quality assurance team before payment.
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── SUCCESS METRICS (narrative stats) ──────────────────────── --}}
<section style="padding:80px 40px; border-top:1px solid rgba(0,0,0,0.06); background:#fff;">
    <div style="max-width:1200px; margin:0 auto;">
        <div data-reveal style="display:grid; grid-template-columns:repeat(4,1fr); gap:24px; text-align:center;" class="four-col">
            <div>
                <div style="font-size:38px; font-weight:700; color:#0b1120;">50K+</div>
                <div style="font-size:13px; color:#64748b;">Tasks completed</div>
            </div>
            <div>
                <div style="font-size:38px; font-weight:700; color:#0b1120;">$2.5M</div>
                <div style="font-size:13px; color:#64748b;">Paid to contributors</div>
            </div>
            <div>
                <div style="font-size:38px; font-weight:700; color:#0b1120;">12K+</div>
                <div style="font-size:13px; color:#64748b;">Active contributors</div>
            </div>
            <div>
                <div style="font-size:38px; font-weight:700; color:#0b1120;">80+</div>
                <div style="font-size:13px; color:#64748b;">Countries represented</div>
            </div>
        </div>
    </div>
</section>

{{-- ── LATEST INSIGHTS (Blog) ──────────────────────────────────── --}}
<section id="insights" style="padding:100px 40px; border-top:1px solid rgba(0,0,0,0.06); background:#f8fafc;">
    <div style="max-width:1200px; margin:0 auto;">
        <div data-reveal style="display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:48px; flex-wrap:wrap; gap:20px;">
            <div>
                <div style="font-size:11px; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; color:#3b82f6; margin-bottom:12px;">Insights</div>
                <h2 style="font-size:clamp(28px,3.5vw,44px); font-weight:600; letter-spacing:-1.5px; margin:0; color:#0b1120;">Latest from the world of AI.</h2>
            </div>
           
        </div>
        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:24px;" class="three-col">
            @foreach([
                ['How RLHF is shaping safer LLMs', 'We break down the role of human feedback in reducing bias and improving model alignment.', 'May 12, 2026', '#3b82f6'],
                ['5 tips to excel as a prompt engineer', 'Learn how to craft effective prompts that yield better AI responses and higher earnings.', 'May 8, 2026', '#8b5cf6'],
                ['The future of remote AI work', 'Why distributed workforces are the backbone of next‑generation AI development.', 'May 2, 2026', '#22c55e'],
            ] as $idx => [$title, $desc, $date, $color])
            <div class="card reveal-card" data-reveal data-delay="{{ $idx * 100 }}" style="padding:24px; background:#fff; border-radius:20px; border:1px solid rgba(0,0,0,0.04); box-shadow:0 2px 8px rgba(0,0,0,0.02);">
                <div style="font-size:12px; color:{{ $color }}; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:8px;">{{ $date }}</div>
                <h3 style="font-size:18px; font-weight:600; margin:0 0 8px; color:#0b1120;">{{ $title }}</h3>
                <p style="font-size:13.5px; color:#475569; line-height:1.5; margin:0 0 16px;">{{ $desc }}</p>
                <a href="#" style="font-size:13px; font-weight:500; color:#3b82f6; text-decoration:none;">Read more →</a>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── LOGO WALL ──────────────────────────────────────────────── --}}
<section style="padding:60px 40px; border-top:1px solid rgba(0,0,0,0.06); background:#fff;">
    <div style="max-width:1200px; margin:0 auto;">
        <div data-reveal style="text-align:center; font-size:12px; color:#94a3b8; text-transform:uppercase; letter-spacing:0.1em; margin-bottom:36px;">
            Trusted by AI teams at 2,000+ companies
        </div>
        <div style="display:grid; grid-template-columns:repeat(6,1fr); border-top:1px solid rgba(0,0,0,0.04); border-left:1px solid rgba(0,0,0,0.04);" class="logo-grid">
            @foreach(['Anthropic', 'OpenAI', 'Google DeepMind', 'Meta AI', 'Hugging Face', 'Scale AI', 'Cohere', 'Midjourney', 'Stability AI', 'NVIDIA', 'Databricks', 'Weights & Biases'] as $idx => $logo)
            <div class="logo-cell" data-reveal data-delay="{{ ($idx % 6) * 60 }}" style="padding:28px 20px; border-right:1px solid rgba(0,0,0,0.04); border-bottom:1px solid rgba(0,0,0,0.04); display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:14px; font-weight:500; letter-spacing:-0.3px; transition:color .2s, background .2s;" onmouseover="this.style.color='#0b1120';this.style.background='rgba(59,130,246,0.04)'" onmouseout="this.style.color='#94a3b8';this.style.background=''">
                {{ $logo }}
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── STATS BAND ─────────────────────────────────────────────── --}}
<section style="padding:100px 40px; border-top:1px solid rgba(0,0,0,0.06); background:#f8fafc;">
    <div style="max-width:1200px; margin:0 auto;">
        <div data-reveal style="display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:48px; flex-wrap:wrap; gap:20px;">
            <div>
                <div style="font-size:11px; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; color:#3b82f6; margin-bottom:12px;">Platform health</div>
                <h2 style="font-size:clamp(28px,3.5vw,44px); font-weight:600; letter-spacing:-1.5px; margin:0; max-width:620px; color:#0b1120; line-height:1.1;">
                    The numbers behind our AI marketplace.
                </h2>
            </div>
            <span style="font-size:12.5px; color:#94a3b8;">Updated live · all time</span>
        </div>
        <div style="display:grid; grid-template-columns:repeat(4,1fr); border-top:1px solid rgba(0,0,0,0.04); border-left:1px solid rgba(0,0,0,0.04);" class="four-col stats-grid">
            @foreach([
                ['$2.5M', 'Paid out to contributors', 'Since launch', null],
                ['50,000+', 'Tasks completed', 'All time', '50000'],
                ['98.7%', 'Approval rate', 'Across all projects', null],
                ['4 min', 'Median payout time', 'After approval', null],
                ['12,000+', 'Active contributors', 'Verified', '12000'],
                ['1,200+', 'Active tasks', 'Right now', '1200'],
                ['4+', 'Project categories', 'All types', null],
                ['< 0.5%', 'Dispute rate', 'Fair & transparent', null],
            ] as $idx => [$v, $l, $s, $target])
            <div data-reveal data-delay="{{ ($idx % 4) * 80 }}" style="padding:32px 28px; border-right:1px solid rgba(0,0,0,0.04); border-bottom:1px solid rgba(0,0,0,0.04); background:#fff;">
                <div class="mono{{ $target ? ' counter' : '' }}"{{ $target ? ' data-target="'.$target.'"' : '' }} style="font-size:clamp(22px,2.5vw,34px); font-weight:600; letter-spacing:-1.2px; line-height:1; margin-bottom:12px; color:#0b1120;">{{ $v }}</div>
                <div style="font-size:13px; color:#0b1120; font-weight:500; margin-bottom:4px;">{{ $l }}</div>
                <div style="font-size:11.5px; color:#94a3b8;">{{ $s }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── TESTIMONIALS ───────────────────────────────────────────── --}}
<section id="testimonials" style="padding:100px 40px; border-top:1px solid rgba(0,0,0,0.06); background:#fff;">
    <div style="max-width:1200px; margin:0 auto;">
        <div data-reveal style="display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:48px; flex-wrap:wrap; gap:20px;">
            <div>
                <div style="font-size:11px; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; color:#3b82f6; margin-bottom:12px;">Loved by contributors</div>
                <h2 style="font-size:clamp(28px,3.5vw,44px); font-weight:600; letter-spacing:-1.5px; margin:0; max-width:620px; color:#0b1120; line-height:1.05;">
                    Real stories from people building AI.
                </h2>
            </div>
            <div style="display:flex; align-items:center; gap:14px;">
                <div style="display:flex; gap:2px; font-size:18px; color:#f59e0b;">★★★★★</div>
                <div>
                    <div style="font-size:14px; font-weight:600; color:#0b1120;">4.9 / 5</div>
                    <div style="font-size:11px; color:#94a3b8;">Avg across 8,500 reviews</div>
                </div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1.4fr 1fr 1fr; gap:18px;" class="testi-grid">
            <div class="card" data-reveal data-delay="0" style="padding:36px; display:flex; flex-direction:column; gap:20px; background:#f8fafc; border-radius:24px; border:1px solid rgba(0,0,0,0.04);">
                <div style="font-size:40px; line-height:1; color:#3b82f6; font-weight:600; font-family:'Poppins',sans-serif;">"</div>
                <p style="font-size:20px; line-height:1.45; font-weight:500; letter-spacing:-0.3px; margin:0; color:#0b1120;">
                    I started with simple data labeling during my commute. Within a month, I was evaluating LLM responses – now I lead a small team of prompt engineers. RemotioX opened a whole new career path.
                </p>
                <div style="display:flex; align-items:center; gap:12px; padding-top:20px; border-top:1px solid rgba(0,0,0,0.06); margin-top:auto;">
                    <div style="width:44px; height:44px; border-radius:50%; background:linear-gradient(135deg,#3b82f6,#60a5fa); display:flex; align-items:center; justify-content:center; color:white; font-weight:600; font-size:16px; flex-shrink:0;">P</div>
                    <div style="flex:1;">
                        <div style="font-size:13.5px; font-weight:500; color:#0b1120;">Priya Nair</div>
                        <div style="font-size:11.5px; color:#64748b;">Prompt Engineer · Level 3</div>
                    </div>
                    <div style="text-align:right;">
                        <div class="mono" style="font-size:15px; font-weight:600; color:#22c55e;">$8,420</div>
                        <div style="font-size:10.5px; color:#94a3b8;">Total earnings</div>
                    </div>
                </div>
            </div>
            @foreach([
                ['M', 'Maya Rhee', 'Data Labeler · Level 2', 'I love the flexibility – I can work after my classes and earn real money. The tasks are clear and the community is super supportive.', '250+ tasks completed', '#8b5cf6'],
                ['T', 'Tom Winters', 'AI Evaluator · Level 3', 'Evaluating model outputs has improved my understanding of NLP. I’ve already recommended RemotioX to three friends.', 'Top 5% performer', '#22c55e'],
            ] as $idx => [$init, $name, $role, $quote, $meta, $color])
            <div class="card" data-reveal data-delay="{{ ($idx + 1) * 120 }}" style="padding:24px; background:#f8fafc; border-radius:20px; border:1px solid rgba(0,0,0,0.04); display:flex; flex-direction:column; gap:16px;">
                <div style="font-size:13px; color:#f59e0b;">★★★★★</div>
                <p style="font-size:14px; line-height:1.55; color:#475569; margin:0; flex:1;">"{{ $quote }}"</p>
                <div style="display:flex; align-items:center; gap:10px; padding-top:16px; border-top:1px solid rgba(0,0,0,0.06);">
                    <div style="width:32px; height:32px; border-radius:50%; background:linear-gradient(135deg,{{ $color }},{{ $color }}99); display:flex; align-items:center; justify-content:center; color:white; font-weight:600; font-size:12px; flex-shrink:0;">{{ $init }}</div>
                    <div style="flex:1; min-width:0;">
                        <div style="font-size:12.5px; font-weight:500; color:#0b1120;">{{ $name }}</div>
                        <div style="font-size:10.5px; color:#64748b;">{{ $role }}</div>
                    </div>
                    <span style="font-size:10px; color:#3b82f6; padding:3px 7px; border-radius:4px; background:#3b82f622; white-space:nowrap; flex-shrink:0;">{{ $meta }}</span>
                </div>
            </div>
            @endforeach
        </div>

        <div style="margin-top:18px; display:grid; grid-template-columns:repeat(3,1fr); gap:18px;" class="three-col">
            @foreach([
                ['A', 'Amara Obi', 'Data Annotator', '#60a5fa', 'The approval process is fast and fair. I’ve never had a dispute.'],
                ['L', 'Lee Jung', 'RLHF Evaluator', '#f59e0b', 'I work 15 hours a week and earn more than my previous part‑time job.'],
                ['C', 'Carlos Gomez', 'Coding Expert', '#22c55e', 'The coding tasks are challenging and help me sharpen my skills.'],
            ] as $idx => [$init, $name, $role, $color, $quote])
            <div class="card" data-reveal data-delay="{{ $idx * 100 }}" style="padding:20px; background:#f8fafc; border-radius:16px; border:1px solid rgba(0,0,0,0.04); display:flex; gap:12px; align-items:flex-start;">
                <div style="width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg,{{ $color }},{{ $color }}99); display:flex; align-items:center; justify-content:center; color:white; font-weight:600; font-size:13px; flex-shrink:0;">{{ $init }}</div>
                <div>
                    <p style="font-size:13px; line-height:1.45; margin:0 0 8px; color:#0b1120;">"{{ $quote }}"</p>
                    <div style="font-size:11px; color:#94a3b8;">{{ $name }} · {{ $role }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── FAQ ────────────────────────────────────────────────────── --}}
<section id="faq" style="padding:100px 40px; border-top:1px solid rgba(0,0,0,0.06); background:#fff;">
    <div style="max-width:1100px; margin:0 auto; display:grid; grid-template-columns:380px 1fr; gap:80px;" class="faq-grid">
        <div data-reveal>
            <div style="font-size:11px; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; color:#3b82f6; margin-bottom:12px;">FAQ</div>
            <h2 style="font-size:clamp(28px,3vw,44px); font-weight:600; letter-spacing:-1.5px; margin:0 0 20px; line-height:1.05; color:#0b1120;">Common questions, answered.</h2>
            <p style="font-size:14.5px; color:#475569; line-height:1.55; margin:0 0 24px;">
                Still have questions? Our support team replies in under 4 hours.
            </p>
            <a href="#" class="btn btn-secondary" style="font-size:13px; padding:8px 16px; background:#f1f5f9; border-radius:40px; color:#0b1120; font-weight:500;">
                Visit help center
                <svg width="13" height="13" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M3 9h12M10 4l5 5-5 5"/></svg>
            </a>
        </div>
        <div x-data="{ open: 0 }" data-reveal data-delay="100">
            @foreach([
                ['What kind of AI projects can I work on?', 'RemotioX offers data labeling (images, text, audio), RLHF (evaluating AI responses), prompt engineering, and specialized expert tasks (coding, math, science, languages).'],
                ['Do I need previous AI experience?', 'No. Many entry-level tasks require only basic computer skills. We also have advanced tasks for experts. Assessments help match you to suitable projects.'],
                ['How much can I earn?', 'Pay varies by task complexity. Simple labeling starts at $5–$10 per task, while expert coding or evaluation can pay $20–$50+ per task. Top contributors earn over $1,500/month.'],
                ['How and when do I get paid?', 'Payments are processed weekly via PayPal, Wise, or bank transfer. Minimum payout is $20. We also support crypto (USDC) on request.'],
                ['Is there a fee for contributors?', 'No fees. You keep 100% of what you earn. Clients pay a small service fee.'],
                ['How are tasks quality‑checked?', 'We use a combination of automated checks and manual reviews. Rejected tasks can be appealed; we have a transparent dispute process.'],
            ] as $idx => [$q, $a])
            <div style="border-bottom:1px solid rgba(0,0,0,0.04); {{ $idx===0 ? 'border-top:1px solid rgba(0,0,0,0.04);' : '' }}">
                <button @click="open === {{ $idx }} ? open = -1 : open = {{ $idx }}"
                        style="width:100%; padding:20px 0; display:flex; justify-content:space-between; align-items:flex-start; gap:20px; background:none; border:none; cursor:pointer; text-align:left; font-family:inherit;">
                    <span style="font-size:16px; font-weight:500; letter-spacing:-0.2px; color:#0b1120;">{{ $q }}</span>
                    <span style="color:#94a3b8; flex-shrink:0; margin-top:2px; transition:transform .18s;" :style="open === {{ $idx }} ? 'transform:rotate(45deg)' : ''">
                        <svg width="16" height="16" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M9 3v12M3 9h12"/></svg>
                    </span>
                </button>
                <div x-show="open === {{ $idx }}" x-transition style="padding-bottom:20px;">
                    <p style="font-size:14px; color:#475569; line-height:1.6; margin:0;">{{ $a }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>



{{-- ── CTA BAND ───────────────────────────────────────────────── --}}
<section id="cta" style="padding:100px 40px; border-top:1px solid rgba(0,0,0,0.06); background:#fff;">
    <div data-reveal style="max-width:1000px; margin:0 auto; text-align:center; padding:80px 60px; background:linear-gradient(135deg, rgba(59,130,246,0.08), #fff); border:1px solid rgba(0,0,0,0.04); border-radius:24px; box-shadow:0 2px 12px rgba(0,0,0,0.02);">
        <h2 style="font-size:clamp(32px,4vw,56px); font-weight:600; letter-spacing:-2px; margin:0 0 20px; color:#0b1120;">
            Your next AI project is one click away.
        </h2>
        <p style="font-size:17px; color:#475569; max-width:560px; margin:0 auto 32px; line-height:1.55;">
            Join thousands of contributors shaping the future of AI. Sign up free, get verified in minutes, and start earning.
        </p>
        <div style="display:inline-flex; gap:12px; flex-wrap:wrap; justify-content:center;">
            <a href="{{ route('membership.apply') }}" class="btn btn-primary" style="padding:12px 22px; font-size:14.5px; background:#3b82f6; border:none; color:#fff; border-radius:40px; font-weight:500;">Get started free</a>
            <a href="{{ route('user.register') }}" class="btn btn-secondary" style="padding:12px 22px; font-size:14.5px; background:#f1f5f9; border:1px solid rgba(0,0,0,0.06); color:#0b1120; border-radius:40px; font-weight:500;">Explore projects</a>
        </div>
    </div>
</section>

{{-- ── FOOTER ──────────────────────────────────────────────────── --}}
<footer style="background:#0b1120; color:rgba(255,255,255,0.7); padding:80px 40px 30px; border-top:1px solid rgba(255,255,255,0.05);">
    <div style="max-width:1200px; margin:0 auto; display:grid; grid-template-columns:2fr 1fr 1fr 1fr 1fr; gap:40px;" class="footer-grid">
        {{-- Brand --}}
        <div>
            <div style="font-size:24px; font-weight:700; color:#fff; letter-spacing:-1px; margin-bottom:12px;">RemotioX</div>
            <p style="font-size:13px; line-height:1.6; max-width:240px; color:rgba(255,255,255,0.5);">
                Connecting talented professionals with flexible AI training and data annotation opportunities worldwide.
            </p>
          
        </div>

        {{-- Quick links --}}
        <div>
            <h4 style="font-size:14px; font-weight:600; color:#fff; margin:0 0 16px;">Platform</h4>
            <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:8px;">
                <li><a href="#projects" style="color:rgba(255,255,255,0.5); text-decoration:none; font-size:13px; transition:color .2s;" onmouseover="this.style.color='#3b82f6'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">Projects</a></li>
                <li><a href="#how" style="color:rgba(255,255,255,0.5); text-decoration:none; font-size:13px; transition:color .2s;" onmouseover="this.style.color='#3b82f6'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">How it works</a></li>
                <li><a href="#benefits" style="color:rgba(255,255,255,0.5); text-decoration:none; font-size:13px; transition:color .2s;" onmouseover="this.style.color='#3b82f6'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">Benefits</a></li>
                
            </ul>
        </div>

        {{-- Resources --}}
        <div>
            <h4 style="font-size:14px; font-weight:600; color:#fff; margin:0 0 16px;">Resources</h4>
            <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:8px;">
                <li><a href="{{ route('contact') }}" style="color:rgba(255,255,255,0.5); text-decoration:none; font-size:13px; transition:color .2s;" onmouseover="this.style.color='#3b82f6'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">Help Center</a></li>
                <li><a href="#testimonials" style="color:rgba(255,255,255,0.5); text-decoration:none; font-size:13px; transition:color .2s;" onmouseover="this.style.color='#3b82f6'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">Testimonials</a></li>
              
                  <li><a href="#faq" style="color:rgba(255,255,255,0.5); text-decoration:none; font-size:13px; transition:color .2s;" onmouseover="this.style.color='#3b82f6'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">FAQ</a></li>
             
            </ul>
        </div>

        {{-- Company --}}
        <div>
            <h4 style="font-size:14px; font-weight:600; color:#fff; margin:0 0 16px;">Company</h4>
            <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:8px;">
                <li><a href="#about" style="color:rgba(255,255,255,0.5); text-decoration:none; font-size:13px; transition:color .2s;" onmouseover="this.style.color='#3b82f6'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">About</a></li>
              
                <li><a href="{{ route('membership.apply') }}" style="color:rgba(255,255,255,0.5); text-decoration:none; font-size:13px; transition:color .2s;" onmouseover="this.style.color='#3b82f6'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">Apply to join</a></li>
                <li><a href="{{ route('contact') }}" style="color:rgba(255,255,255,0.5); text-decoration:none; font-size:13px; transition:color .2s;" onmouseover="this.style.color='#3b82f6'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">Contact</a></li>
            </ul>
        </div>

        {{-- Legal --}}
      <div>
    <h4 style="font-size:14px; font-weight:600; color:#fff; margin:0 0 16px;">Legal</h4>
    <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:8px;">
        <li><a href="{{ route('privacy-policy') }}" style="color:rgba(255,255,255,0.5); text-decoration:none; font-size:13px; transition:color .2s;" onmouseover="this.style.color='#3b82f6'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">Privacy Policy</a></li>
        <li><a href="{{ route('terms') }}" style="color:rgba(255,255,255,0.5); text-decoration:none; font-size:13px; transition:color .2s;" onmouseover="this.style.color='#3b82f6'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">Terms of Service</a></li>
        <li><a href="{{ route('cookie-policy') }}" style="color:rgba(255,255,255,0.5); text-decoration:none; font-size:13px; transition:color .2s;" onmouseover="this.style.color='#3b82f6'" onmouseout="this.style.color='rgba(255,255,255,0.5)'">Cookie Policy</a></li>
    </ul>
</div>
    </div>

    {{-- Bottom bar --}}
    <div style="max-width:1200px; margin:40px auto 0; padding-top:24px; border-top:1px solid rgba(255,255,255,0.05); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <span style="font-size:12px; color:rgba(255,255,255,0.3);">&copy; {{ date('Y') }} RemotioX. All rights reserved.</span>
        <span style="font-size:12px; color:rgba(255,255,255,0.3);">
            Built with ❤️ for the AI community
        </span>
        <a href="#home" style="display:inline-flex; align-items:center; gap:6px; font-size:12px; color:rgba(255,255,255,0.4); text-decoration:none; transition:color .2s;" onmouseover="this.style.color='#3b82f6'" onmouseout="this.style.color='rgba(255,255,255,0.4)'">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3v12M5 10l7-7 7 7"/></svg>
            Back to top
        </a>
    </div>
</footer>

{{-- ── STYLES ──────────────────────────────────────────────────── --}}
<style>
/* (same as before, plus smooth scroll for anchor links) */
html { scroll-behavior: smooth; }
@keyframes pulse-urgent {
    0%   { box-shadow: 0 0 0 0 rgba(34,197,94,0.55); }
    70%  { box-shadow: 0 0 0 8px rgba(34,197,94,0); }
    100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); }
}
@keyframes fadeUp {
    from { opacity:0; transform:translateY(28px); }
    to   { opacity:1; transform:translateY(0); }
}
@keyframes fadeDown {
    from { opacity:0; transform:translateY(-16px); }
    to   { opacity:1; transform:translateY(0); }
}
@keyframes fadeIn {
    from { opacity:0; }
    to   { opacity:1; }
}
@keyframes floatCard {
    0%,100% { transform: translateY(0px) rotate(0deg); }
    33%     { transform: translateY(-10px) rotate(0.4deg); }
    66%     { transform: translateY(-5px) rotate(-0.3deg); }
}
@keyframes cardEntrance {
    from { opacity:0; transform:translateY(20px) scale(0.96); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}
@keyframes cursorBlink {
    0%, 100% { opacity:1; }
    50%       { opacity:0; }
}
@keyframes cursorFade {
    to { opacity:0; }
}
.typing-cursor {
    color: #60a5fa;
    font-weight: 300;
    animation: cursorBlink 0.65s step-end infinite;
}
.typing-cursor.done {
    animation: cursorBlink 0.65s step-end 3, cursorFade 0.5s ease 1.95s forwards;
}
.anim-hero-badge { animation: fadeDown 0.55s cubic-bezier(.22,1,.36,1) both; }
.anim-hero-p     { animation: fadeUp 0.65s cubic-bezier(.22,1,.36,1) 0.26s both; }
.anim-hero-actions { animation: fadeUp 0.65s cubic-bezier(.22,1,.36,1) 0.4s both; }
.anim-hero-stats   { animation: fadeUp 0.65s cubic-bezier(.22,1,.36,1) 0.54s both; }
.hero-float-card {
    animation: cardEntrance 0.7s cubic-bezier(.22,1,.36,1) both, floatCard 6s ease-in-out infinite;
    animation-delay: var(--card-delay, 0s), calc(var(--card-delay, 0s) + 0.7s);
}
[data-reveal] {
    opacity: 0;
    transform: translateY(28px);
    transition: opacity 0.65s cubic-bezier(.22,1,.36,1), transform 0.65s cubic-bezier(.22,1,.36,1);
}
[data-reveal].is-visible { opacity: 1; transform: translateY(0); }
.hero-cards { display: none; }
@media (min-width:1024px) { .hero-cards { display: block; } }
@media (max-width:1024px) {
    .hero-grid       { grid-template-columns: 1fr !important; }
    .testi-grid      { grid-template-columns: 1fr !important; }
    .footer-grid     { grid-template-columns: 1fr 1fr !important; }
    .faq-grid        { grid-template-columns: 1fr !important; }
    .three-col       { grid-template-columns: 1fr 1fr !important; }
    .four-col        { grid-template-columns: 1fr 1fr !important; }
    .stats-grid      { grid-template-columns: 1fr 1fr !important; }
    .logo-grid       { grid-template-columns: repeat(3,1fr) !important; }
    .about-grid, .quality-grid { grid-template-columns: 1fr !important; }
}
@media (max-width:640px) {
    .three-col, .four-col { grid-template-columns: 1fr !important; }
    .logo-grid { grid-template-columns: repeat(2,1fr) !important; }
    .footer-grid { grid-template-columns: 1fr 1fr !important; }
    section { padding-left: 20px !important; padding-right: 20px !important; }
    footer  { padding-left: 20px !important; padding-right: 20px !important; }
}
@media (prefers-reduced-motion: reduce) {
    .anim-hero-badge, .anim-hero-p, .anim-hero-actions, .anim-hero-stats,
    .typing-cursor, .hero-float-card, [data-reveal] {
        animation: none !important; transition: none !important;
        opacity: 1 !important; transform: none !important;
    }
}
</style>

@push('scripts')
<script>
(function() {
    // Typewriter – static text from window._heroTyper
    (function() {
        var el     = document.getElementById('hero-typed');
        var cursor = document.querySelector('.typing-cursor');
        if (!el) return;

        var t = window._heroTyper || ['Earn by ', 'labeling', ',\nevaluating AI', ' Shaping smarter models.'];
        var parts = [[t[0], null], [t[1], '#22c55e'], [t[2], null], [t[3], '#60a5fa']];

        var chars = [];
        parts.forEach(function(p) {
            p[0].split('').forEach(function(ch) { chars.push({ch: ch, color: p[1]}); });
        });

        var idx = 0, speed = 44;

        function buildHTML(count) {
            var html = '', inColor = null, i = 0;
            while (i < count) {
                var c = chars[i];
                if (c.ch === '\n') {
                    if (inColor) { html += '</span>'; inColor = null; }
                    html += '<br>';
                } else if (c.color !== inColor) {
                    if (inColor) html += '</span>';
                    if (c.color) { html += '<span style="color:' + c.color + ';">'; }
                    inColor = c.color || null;
                    html += c.ch;
                } else {
                    html += c.ch;
                }
                i++;
            }
            if (inColor) html += '</span>';
            return html;
        }

        function type() {
            if (idx > chars.length) { cursor.classList.add('done'); return; }
            el.innerHTML = buildHTML(idx);
            idx++;
            setTimeout(type, speed);
        }

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            el.innerHTML = buildHTML(chars.length);
            cursor.classList.add('done');
            return;
        }
        setTimeout(type, 450);
    })();

    // Hero card float delays
    document.querySelectorAll('.hero-float-card').forEach(function(card) {
        var delay = card.style.animationDelay || '0s';
        card.style.setProperty('--card-delay', delay);
        card.style.animationDelay = delay + ', calc(' + delay + ' + 0.7s)';
    });

    // Scroll reveal
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                var el = entry.target;
                setTimeout(function() { el.classList.add('is-visible'); }, parseInt(el.dataset.delay || 0, 10));
                observer.unobserve(el);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    document.querySelectorAll('[data-reveal]').forEach(function(el) { observer.observe(el); });

    // Number counters
    function animateCounter(el) {
        var target = parseInt(el.dataset.target, 10);
        if (!target) return;
        var original = el.textContent, duration = 1400, start = null;
        function easeOut(t) { return 1 - Math.pow(1 - t, 3); }
        function step(ts) {
            if (!start) start = ts;
            var p = Math.min((ts - start) / duration, 1);
            el.textContent = Math.round(easeOut(p) * target).toLocaleString();
            if (p < 1) requestAnimationFrame(step); else el.textContent = original;
        }
        requestAnimationFrame(step);
    }
    var cObs = new IntersectionObserver(function(entries) {
        entries.forEach(function(e) { if (e.isIntersecting) { animateCounter(e.target); cObs.unobserve(e.target); } });
    }, { threshold: 0.5 });
    document.querySelectorAll('.counter[data-target]').forEach(function(el) { cObs.observe(el); });
})();
</script>
@endpush

@endsection