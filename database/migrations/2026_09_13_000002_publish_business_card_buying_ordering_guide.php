<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $publishedAt = $now->copy()->subMinute();

        $categoryId = DB::table('categories')
            ->where('slug', 'getting-started')
            ->value('id');

        if ($categoryId === null) {
            DB::table('categories')->insertOrIgnore([
                'name' => 'Getting Started',
                'slug' => 'getting-started',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $categoryId = DB::table('categories')
                ->where('slug', 'getting-started')
                ->value('id');
        }

        $adminId = DB::table('admins')
            ->where('email', 'admin@admin.com')
            ->value('id')
            ?? DB::table('admins')->orderBy('id')->value('id');

        if ($adminId === null) {
            $adminId = DB::table('admins')->insertGetId([
                'name' => 'Admin',
                'email' => 'admin@admin.com',
                'password' => Hash::make('password'),
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if ($categoryId === null || $adminId === null) {
            throw new RuntimeException(
                'The Getting Started category and an admin author are required before publishing the guide.',
            );
        }

        DB::table('posts')->updateOrInsert(
            ['slug' => 'business-card-buying-ordering-guide'],
            [
                'title' => '名片选购与下单指南：材质、尺寸、工艺与交期',
                'body' => self::body(),
                'featured_image' => '/images/blog/business-card-buying-ordering-guide-featured.webp',
                'category_id' => $categoryId,
                'admin_id' => $adminId,
                'is_published' => true,
                'published_at' => $publishedAt,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );
    }

    public function down(): void
    {
        DB::table('posts')
            ->where('slug', 'business-card-buying-ordering-guide')
            ->delete();
    }

    private static function body(): string
    {
        return <<<'HTML'
<p>第一次下单名片，不需要先记住所有纸张克重和印刷术语。只要先确定数量、想要的触感，以及是否需要特殊工艺，就能快速缩小选择范围。本指南把材质、尺寸、工艺、文件、生产、配送和售后整理成一条容易执行的路线。</p>

<blockquote>
    <p><strong>快速路线：</strong>选材质 → 定尺寸与数量 → 选工艺 → 准备文件 → 审稿生产 → 配送收货。</p>
</blockquote>

<p>如果是第一次下单，建议先阅读前两部分完成选材；如果已经有明确想法，可以直接查看尺寸、工艺和下单流程。文中的价格沿用原资料中的“每张起价”示例，最终价格会随数量、尺寸、材质、厚度、工艺和配送方式变化。税费、运费及特殊工艺费是否包含，请以下单页面的结算结果或客服确认为准。</p>

<h2>第一次买名片，先回答三个问题</h2>

<p>不需要先研究所有纸张。先回答下面三个问题，就能把选择范围缩小到适合自己的产品类别。</p>

<ol>
    <li><strong>需要多少张？</strong>50 张起适合小批量试做或精品名片；200 张起更适合预算优先、需求稳定的常规名片。</li>
    <li><strong>想要什么感觉？</strong>重视纸张触感和压印层次，可以看 Letterpress 棉质名片；希望有更多纸张和综合工艺选择，可以看超级名片；喜欢厚实、简洁、有分量的手感，可以看豪华名片。</li>
    <li><strong>是否需要特殊工艺？</strong>烫金、压凹、模切、边缘加工、激光切割、冷烫和局部凸起 UV 并非所有材质都支持。先选产品类别，再确认可用工艺。</li>
</ol>

<h3>如果仍然拿不准</h3>

<ul>
    <li>想要明显的纸张触感、压印深浅和手工感：选 Letterpress 棉质名片。</li>
    <li>想从多种纸张与工艺中搭配，又希望 50 张起做：选超级名片。</li>
    <li>想要厚实、简洁、质感直接：选豪华名片。</li>
    <li>已经确定要 PVC 材质和固定圆角：选 PVC 名片。</li>
    <li>已经确定要金属材质：选金属名片。</li>
    <li>数量较多、预算优先，并能接受拼版印刷的合理色差：选经典名片。</li>
</ul>

<p><strong>请注意：</strong>不要只看“克重”判断厚度。不同纸张的密度和结构不同，克重相近时手感也可能不同；应以页面标注的毫米厚度和实物材质效果为主要参考。</p>

<h2>六类名片怎么选</h2>

<p>下面按“适合谁—核心规格—下单前确认”说明。若只想快速做决定，先看每一类开头的“适合你”。</p>

<h3>1. Letterpress 棉质名片</h3>

<p><strong>适合你：</strong>重视纸张触感、压印层次和精品工艺；适合品牌主理人、工作室、婚礼与高端服务场景。</p>

<ul>
    <li><strong>起订量：</strong>50 张；原资料起价为 ¥1.9/张起。</li>
    <li><strong>纸张：</strong>300–700g 棉质纸；成品厚度约 0.35–1.1mm。</li>
    <li><strong>尺寸：</strong>最大 3.5 × 2.1in 范围内可定制。</li>
    <li><strong>可选工艺：</strong>专色、满版彩色印刷、压凹/压印、烫金、模切、边缘加工和激光切割。</li>
</ul>

<p><strong>下单前确认：</strong>复杂工艺会影响可实现的线条、套准和生产时间，请在设计前确认工艺组合。</p>

<h3>2. 超级名片</h3>

<p><strong>适合你：</strong>希望小批量起做，同时拥有较多纸张、覆膜与综合工艺选择。</p>

<ul>
    <li><strong>起订量：</strong>50 张；原资料起价为 ¥0.4/张起。</li>
    <li><strong>纸张：</strong>8 种 350g 纹理纸，厚约 0.4mm；另有 320g 铜版纸约 0.4mm、640g 铜版纸约 0.8mm。</li>
    <li><strong>尺寸：</strong>最大 3.5 × 2.1in 范围内可定制。</li>
    <li><strong>纹理纸可选：</strong>四色印刷、烫金和模切。</li>
    <li><strong>320g/640g 铜版纸可选：</strong>冷烫、局部凸起 UV、模切及多种覆膜。</li>
</ul>

<p><strong>下单前确认：</strong>先确认纸张，再确认工艺；同一工艺在纹理纸和铜版纸上的效果会不同。</p>

<h3>3. 豪华名片</h3>

<p><strong>适合你：</strong>喜欢厚实纸张、简洁而有分量的视觉与手感，并希望 50 张起做。</p>

<ul>
    <li><strong>起订量：</strong>50 张；原资料起价为 ¥0.7/张起。</li>
    <li><strong>纸张：</strong>4 种 700g 纹理纸；成品厚度约 0.8mm。</li>
    <li><strong>尺寸：</strong>最大 3.5 × 2.1in 范围内可定制。</li>
    <li><strong>可选工艺：</strong>四色印刷、烫金和模切。</li>
</ul>

<p><strong>下单前确认：</strong>如果设计中有大面积深色或细小反白文字，请先让文件审核确认印刷适配性。</p>

<h3>4. PVC 名片</h3>

<p><strong>适合你：</strong>已经明确需要 PVC 材质、标准尺寸和圆角外形。</p>

<ul>
    <li><strong>起订量：</strong>50 张；原资料起价为 ¥0.6/张起。</li>
    <li><strong>厚度：</strong>0.38mm、0.76mm、0.84mm。</li>
    <li><strong>固定尺寸：</strong>3.37 × 2.13in，圆角。</li>
    <li><strong>表面可选：</strong>磨砂、哑光和亮光。</li>
</ul>

<p><strong>下单前确认：</strong>PVC 的尺寸与圆角为固定规格；如需非标准外形，请先联系客服确认是否可做。</p>

<h3>5. 金属名片</h3>

<p><strong>适合你：</strong>希望以金属材质形成鲜明识别度，并接受较长生产周期。</p>

<ul>
    <li><strong>起订量：</strong>50 张；原资料起价为 ¥4.6/张起。</li>
    <li><strong>厚度：</strong>0.3mm 或 0.5mm。</li>
    <li><strong>尺寸：</strong>89 × 51mm（约 3.50 × 2.01in）、85 × 54mm（约 3.35 × 2.13in）、80 × 50mm（约 3.15 × 1.97in）。</li>
    <li><strong>工艺说明：</strong>原资料提到可选多种颜色与工艺，但未列出具体项目。</li>
</ul>

<p><strong>下单前确认：</strong>金属工艺、颜色、文字最小尺寸和镂空限制，请在设计前向客服索取当前可选清单。</p>

<h3>6. 经典名片</h3>

<p><strong>适合你：</strong>数量较多、预算优先、版式已经稳定，并能接受拼版印刷的正常批次色差。</p>

<ul>
    <li><strong>起订量：</strong>200 张；原资料起价为 ¥0.065/张起。</li>
    <li><strong>材质：</strong>2 种覆膜/UV 铜版纸（厚约 0.32mm）及 6 种无涂层纹理纸。</li>
    <li><strong>常见尺寸：</strong>3.5 × 2in；最大 85 × 85mm。</li>
    <li><strong>印刷方式：</strong>采用拼版印刷，适合常规四色设计。</li>
</ul>

<p><strong>下单前确认：</strong>原资料对正方形尺寸同时出现 2.5 × 2.5in 与 2.56 × 2.56in 两种写法。需要正方形时，请以下单页可选规格为准并先联系客服确认；如果品牌色要求严格，也建议先沟通色差控制。</p>

<h2>尺寸怎么选</h2>

<p>先确认产品类别是否支持定制尺寸，再决定成品大小。不要在设计完成后才发现材质只能做固定尺寸。</p>

<h3>最省心的选择顺序</h3>

<ol>
    <li><strong>先选产品类别：</strong>Letterpress、超级和豪华可在最大范围内定制；PVC 为固定尺寸；金属有三种规格；经典名片以页面可选尺寸为准。</li>
    <li><strong>再定成品尺寸：</strong>常见横版名片可参考 3.5 × 2in；不同国家、卡包和使用场景会影响尺寸偏好。</li>
    <li><strong>最后建立设计文件：</strong>文件必须按最终成品尺寸制作，并为裁切预留出血；安全边距、出血数值和模板版本以下单页模板为准。</li>
</ol>

<h3>各产品的尺寸提示</h3>

<ul>
    <li><strong>Letterpress、超级和豪华：</strong>最大 3.5 × 2.1in 范围内可定制。</li>
    <li><strong>PVC：</strong>固定 3.37 × 2.13in，圆角。</li>
    <li><strong>金属：</strong>89 × 51mm、85 × 54mm、80 × 50mm。</li>
    <li><strong>经典：</strong>常见 3.5 × 2in，最大 85 × 85mm；正方形规格下单前确认。</li>
</ul>

<p><strong>请注意：</strong>“比例不变”和“尺寸不变”不是同一件事。缩放设计时，名片长宽比例可以保持，但文字、线条和二维码仍可能变得过小。提交前请按最终成品尺寸检查可读性。</p>

<h3>可以直接告诉客服的需求</h3>

<p>“我需要横版名片，预计做 100 张，想要有纸张纹理和轻微烫金。请推荐可以使用的材质、厚度、尺寸与文件模板，并说明预计生产时间和配送方式。”</p>

<h2>表面与工艺，用简单的话理解</h2>

<p>“表面处理”决定整体光泽和触感；“特殊工艺”通常只作用于文字、标志或局部图形。两者可以组合，但要看材质是否支持。</p>

<h3>三种常见表面</h3>

<ul>
    <li><strong>哑光：</strong>反光少，视觉更克制；原资料说明可用记号笔书写。</li>
    <li><strong>亮光：</strong>表面有明显光泽，颜色和图片看起来更鲜明，也会产生反光。</li>
    <li><strong>触感膜：</strong>属于哑光方向，但触摸更柔软、细腻，常用于强调高级触感。</li>
</ul>

<h3>常见特殊工艺</h3>

<ul>
    <li><strong>压凹/压印：</strong>通过压力形成凹陷或压痕，适合突出纸张触感。</li>
    <li><strong>烫金：</strong>让局部图文呈现金属箔效果；可用范围取决于材质与线条细节。</li>
    <li><strong>冷烫：</strong>原资料中用于部分超级名片铜版纸选项；具体颜色与适配设计需要确认。</li>
    <li><strong>局部凸起 UV：</strong>让局部图文微微凸起并有光泽；原资料中用于部分超级名片铜版纸选项。</li>
    <li><strong>模切：</strong>把名片裁成非普通矩形或局部特殊外形；需要检查圆角、尖角和细窄结构。</li>
    <li><strong>边缘加工/激光切割：</strong>适用于原资料列明的 Letterpress 选项，需要单独确认设计限制。</li>
</ul>

<p><strong>请注意：</strong>工艺不是越多越好。第一次下单可以先选一种主工艺，让标志或关键信息成为视觉重点；工艺叠加越多，文件要求、成本与生产时间通常越高。</p>

<h2>从选择到收货：完整下单流程</h2>

<p>按下面六步准备，客服与文件审核会更容易判断是否能做，也能减少反复修改。</p>

<ol>
    <li><strong>选择产品类别：</strong>在 Letterpress、超级、豪华、PVC、金属和经典中先选一类。拿不准时，提供数量、预算和想要的触感，让客服推荐。</li>
    <li><strong>确认规格：</strong>一次说清数量、材质、厚度、成品尺寸、表面处理、特殊工艺、单面或双面。</li>
    <li><strong>准备设计：</strong>可以自行设计、下载模板制作、提供内容由 Inkpavo 协助套用模板，或另行选择一对一设计服务。</li>
    <li><strong>提交文件并人工检查：</strong>文件会结合所选材质与工艺检查。审核重点包括尺寸、出血、文字与线条、图片清晰度、颜色模式及工艺位置。</li>
    <li><strong>确认后进入生产：</strong>确认规格、文件与付款信息后排产。进入生产前，请再次检查姓名、职位、电话、邮箱、网址、二维码和数量。</li>
    <li><strong>选择物流并收货验货：</strong>生产完成后按已选物流发出。收货后尽快检查数量、文字、颜色、裁切、材质和工艺效果；发现问题时保留包装与证据。</li>
</ol>

<h3>文件提交前务必检查这七项</h3>

<ol>
    <li>姓名、职位、电话、邮箱和网址是否正确。</li>
    <li>二维码是否能正常扫描，并指向正确页面。</li>
    <li>文件尺寸是否与最终成品尺寸一致。</li>
    <li>背景与图片是否按模板预留出血。</li>
    <li>文字和标志是否留在安全区域内。</li>
    <li>细线、小字和反白字是否适合所选纸张与工艺。</li>
    <li>正反面方向、单面/双面、数量与版本是否正确。</li>
</ol>

<h2>多久能收到：生产时间加物流时间</h2>

<p>到货时间不是只看快递。先完成生产，再进入运输；复杂工艺、金属材质、文件修改与节假日都可能影响整体时间。</p>

<h3>生产时间参考</h3>

<ul>
    <li>最快生产：约 2–3 个工作日，具体取决于纸张与工艺。</li>
    <li>带工艺订单：通常约 5–7 个工作日。</li>
    <li>金属名片：通常约 15–20 个工作日。</li>
</ul>

<h3>物流时间参考</h3>

<ul>
    <li>快速物流：约 3–7 个工作日。</li>
    <li>标准物流：约 10–18 个工作日。</li>
</ul>

<h3>如何估算到货日</h3>

<p>预计到货时间 ≈ 文件确认后的生产时间 + 物流时间。</p>

<p>例如，普通带工艺订单若生产 5–7 个工作日，再选择快速物流 3–7 个工作日，整体可先按约 8–14 个工作日预留。这只是估算，不等同于承诺到货日。</p>

<p><strong>截单时间：</strong>原资料中的截单时间为周一至周六、北京时间 18:00 前。文件尚未确认、信息不完整或超过截单时间时，实际排产可能顺延。</p>

<p><strong>请注意：</strong>有活动、展会或婚礼等固定日期时，不要只按最短天数倒推。请把“必须收到的日期”和收货地区提前告诉客服，并预留修改、生产、清关或派送波动时间。</p>

<h2>收货验货与售后</h2>

<p>下面按原资料整理为客户可执行的步骤。最终处理以订单信息、提交证据和售后审核为准。</p>

<h3>发现问题时，先做三件事</h3>

<ol>
    <li><strong>暂停使用并保留包装：</strong>先不要分发、丢弃或自行返工，保留外包装、标签和全部产品。</li>
    <li><strong>拍摄清晰证据：</strong>提供能看清问题的照片或视频，同时拍摄问题成品与已确认设计稿的对比。</li>
    <li><strong>按问题类型补充测量：</strong>涉及裁切偏差时，用标准测量工具拍摄；涉及批量问题时，说明受影响数量与总数量。</li>
</ol>

<h3>原资料中的处理原则</h3>

<ul>
    <li><strong>文字错误：</strong>若错误来自客户提交文件，不承担责任；若为 Inkpavo 制作错误，免费重印并采用标准物流。</li>
    <li><strong>轻微色差：</strong>原资料将 5%–15% 归为轻微色差，可按订单金额补偿 15%，或提供下次订单 15% 优惠券。</li>
    <li><strong>斜切偏差超过 1mm：</strong>整批返工。</li>
    <li><strong>圆角或异形裁切有毛边：</strong>按不良数量退款。</li>
    <li><strong>材质不符：</strong>全额退款并承担销毁费用。原资料提到需要视频证明，但未明确具体格式；销毁前请先向客服确认拍摄要求。</li>
    <li><strong>使用性缺陷：</strong>按受影响数量比例补偿；例如 20% 产品有问题，按 20% 货款计算。</li>
</ul>

<p><strong>请注意：</strong>不同屏幕、纸张、墨色与印刷批次会影响颜色观感。若品牌色必须严格一致，请在下单前主动说明并确认可采用的色彩管理或打样方式。</p>

<h2>下单前最后看一遍</h2>

<p>把下面信息一次性准备好，就可以进入咨询、报价与文件确认：</p>

<ol>
    <li><strong>产品类别：</strong>Letterpress / 超级 / 豪华 / PVC / 金属 / 经典。</li>
    <li><strong>数量与预算：</strong>需要多少张；预算更重视单价还是质感。</li>
    <li><strong>材质与厚度：</strong>纸张、PVC 或金属；希望轻薄、标准还是厚实。</li>
    <li><strong>成品尺寸：</strong>横版、竖版、正方形或定制；是否为固定尺寸。</li>
    <li><strong>表面与工艺：</strong>哑光、亮光、触感膜、压印、烫金、UV、模切等。</li>
    <li><strong>设计文件：</strong>最终文字、图片、标志、二维码、正反面与模板。</li>
    <li><strong>时间与物流：</strong>必须收到日期、收货地区、快速或标准物流。</li>
</ol>

<h3>可以直接复制给客服的需求清单</h3>

<ul>
    <li>用途：__________</li>
    <li>产品类别：__________</li>
    <li>数量：__________ 张</li>
    <li>材质 / 厚度：__________</li>
    <li>成品尺寸：__________</li>
    <li>表面 / 特殊工艺：__________</li>
    <li>单面或双面：__________</li>
    <li>必须收到日期与收货地区：__________</li>
    <li>是否已有可印刷文件：是 / 否</li>
</ul>

<p>报价、可选材质、工艺能力、模板与交期可能随产品更新。正式下单时，请以下单页面、最终订单确认和客服回复为准。</p>

<p>如果你已经知道数量和预算，可以从 <a href="/business-cards">名片产品页</a>开始；需要文件或版式协助，可以查看 <a href="/business-card-design-service">名片设计服务</a>。</p>
HTML;
    }
};
