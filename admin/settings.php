<?php

/**
 * Settings view rendered inside the OptiCore admin screen.
 *
 * The markup deliberately keeps the structure simple—JavaScript augments the
 * form with AJAX submission, dependency awareness, and section navigation. This
 * file should remain PHP-centric so it can be loaded directly if JS fails.
 *
 * @var Opticore\Framework $framework Injected via inline instantiation below.
 */
$framework = new Opticore\Framework();
?>

<form method="post" action="" class="flex flex-col gap-4 opticore-settings-form">
    <?php
    // Nonce used for both traditional submissions and AJAX requests.
    wp_nonce_field('opticore-nonce', 'opticore-nonce', false);
    ?>
    <input type="hidden" name="action" value="opticore-save-settings">

    <header class="bg-linear-to-br from-sky-400 to-violet-500 flex justify-between items-center p-6 shadow-lg rounded-lg">
        <h1 class="p-0 text-white! flex! items-center gap-4">
            <div class="relative p-3 before:absolute before:bg-white/50 before:w-full before:h-full before:z-10 before:rounded-lg before:inset-y-0 before:inset-x-0 before:rotate-20">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="currentColor" d="M18.725 21.675q-.275.1-.525-.062t-.325-.488q-.05-.275.088-.55t.412-.4q.95-.425 1.513-1.275t.562-1.9t-.562-1.9t-1.513-1.275q-.3-.125-.425-.4t-.075-.55q.075-.325.325-.487t.525-.063q1.475.55 2.35 1.825T21.95 17t-.875 2.85t-2.35 1.825m-3.525-.05q-.5-.2-.938-.475t-.812-.65t-.65-.812t-.475-.938q-.1-.275.063-.525t.462-.325q.275-.05.538.1t.387.425q.125.3.313.55t.412.475t.488.413t.537.312t.413.4t.087.55q-.075.3-.312.45t-.513.05m1.15-2.875q-.125.075-.263.013t-.137-.213v-3.1q0-.15.138-.212t.262.012l2.375 1.55q.125.075.125.2t-.125.2zm-3.5-2.65q-.3-.075-.462-.325t-.063-.525q.2-.5.475-.937t.65-.813t.813-.65t.937-.475q.275-.1.513.05t.312.45q.05.275-.088.55t-.412.4t-.537.313t-.488.412t-.413.475t-.312.55q-.125.275-.387.425t-.538.1M10.575 22q-.675 0-1.037-.45t-.463-1.1L8.85 18.8q-.325-.125-.612-.3t-.563-.375l-1.55.65q-.625.275-1.25.05t-.975-.8l-1.175-2.05q-.35-.575-.2-1.225t.675-1.075l1.325-1Q4.5 12.5 4.5 12.337v-.675q0-.162.025-.337l-1.325-1Q2.675 9.9 2.525 9.25t.2-1.225L3.9 5.975q.35-.575.975-.8t1.25.05l1.55.65q.275-.2.575-.375t.6-.3l.225-1.65q.1-.65.588-1.1T10.825 2h2.35q.675 0 1.163.45t.587 1.1l.225 1.65q.325.125.613.3t.562.375l1.55-.65q.625-.275 1.25-.05t.975.8l1.175 2.05q.35.575.2 1.225t-.675 1.075l-.6.45q-.325.275-.725.212T18.8 10.6t-.225-.725t.375-.675l.475-.35l-.975-1.7l-2.475 1.05q-.55-.575-1.213-.962t-1.437-.588L13 4h-1.975l-.35 2.65q-.775.2-1.437.588t-1.213.937L5.55 7.15l-.975 1.7l2.15 1.6q-.125.375-.175.75t-.05.8q0 .4.05.775t.175.75l-2.15 1.625l.975 1.7l2.475-1.05q.425.425.913.763t1.062.562q.025 1.1.363 2.088t.912 1.812q.2.3-.025.638t-.675.337M12.05 8.5q-1.45 0-2.475 1.013T8.55 12q0 .525.15 1.025t.45.95q.275.375.713.475t.787-.175q.35-.25.413-.663t-.213-.712q-.15-.2-.225-.413T10.55 12q0-.625.438-1.062t1.062-.438q.25 0 .488.088t.437.237q.3.225.675.163t.625-.413t.163-.775T14 9.1q-.35-.3-.85-.45t-1.1-.15" />
                </svg>
            </div>
            <?php _e('Settings', 'opticore'); ?>
        </h1>

        <div class="flex gap-4 items-center">
            <?php
            // Reset is currently visual only; JS enhancement may handle the behaviour in the future.
            ?>
            <button type="reset" class="shadow-lg flex gap-2 py-2 px-4 bg-slate-100 items-center text-zinc-800 font-bold! rounded-full cursor-pointer group">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 32 32" class="group-hover:animate-spin" aria-hidden="true">
                    <path fill="currentColor" d="M16 4c-5.113 0-9.383 3.16-11.125 7.625l1.844.75C8.176 8.641 11.71 6 16 6c3.242 0 6.133 1.59 7.938 4H20v2h7V5h-2v3.094A11.94 11.94 0 0 0 16 4m9.281 15.625C23.824 23.359 20.29 26 16 26c-3.277 0-6.156-1.613-7.969-4H12v-2H5v7h2v-3.094C9.188 26.386 12.395 28 16 28c5.113 0 9.383-3.16 11.125-7.625z" />
                </svg>
                <?php _e('Reset All', 'opticore'); ?>
            </button>

            <button type="submit" class="shadow-lg flex gap-2 py-2 px-4 bg-sky-400 items-center text-white font-bold! rounded-full cursor-pointer group">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 32 32" class="group-hover:animate-bounce" aria-hidden="true">
                    <path fill="currentColor" d="M5 5v22h22V9.594l-.281-.313l-4-4L22.406 5zm2 2h3v6h12V7.437l3 3V25h-2v-9H9v9H7zm5 0h4v2h2V7h2v4h-8zm-1 11h10v7H11z" />
                </svg>
                <?php _e('Save Changes', 'opticore'); ?>
            </button>
        </div>
    </header>

    <div class="flex gap-4">
        <div class="flex flex-col w-75">
            <div class="bg-white shadow-lg p-4 rounded-lg flex flex-col gap-4 sticky top-11">
                <?php
                $counter = 0;
                foreach ($framework->options() as $index => $option):
                    // Using anchors lets the JS controller handle display without reflowing markup.
                    ?>
                    <a href="#<?php echo $option['id'] ?? 'section-' . $index; ?>"
                       class="settings-menu-item<?php echo $counter === 0 ? ' active' : ''; ?> p-3 text-zinc-900! hover:bg-linear-to-br hover:from-sky-400 hover:to-violet-500 hover:text-white! [&.active]:bg-linear-to-br [&.active]:from-sky-400 [&.active]:to-violet-500 [&.active]:text-white! text-lg flex gap-3 items-center font-medium rounded-lg hover:-translate-y-1 transition-transform! duration-200! focus:outline-none focus:ring-0!">
                        <span class="material-symbols-outlined text-3xl! aspect-square"><?php echo $option['icon'] ?? 'settings'; ?></span>
                        <?php echo $option['title']; ?>
                    </a>
                    <?php
                    $counter++;
                endforeach;
                ?>
            </div>
            <span class="text-zinc-500 flex justify-center py-3">
                <?php printf('%s %s', __('Version', 'opticore'), OPTICORE_PLUGIN_VERSION); ?>
            </span>
        </div>

        <div class="flex flex-col grow">
            <?php
            $counter = 0;
            foreach ($framework->options() as $index => $option):
                $sectionId = $option['id'] ?? 'section-' . $index;
                ?>
                <section class="settings-menu-section bg-white shadow-lg p-4 min-w-70 rounded-lg"
                         id="section-<?php echo $sectionId; ?>"
                    <?php echo $counter !== 0 ? 'style="display:none;"' : ''; ?>>

                    <?php if (!empty($option['title'])): ?>
                        <h3 class="text-xl! flex! items-center my-0! gap-3">
                            <span class="material-symbols-outlined text-3xl! aspect-square"><?php echo $option['icon'] ?? 'settings'; ?></span>
                            <?php echo esc_html($option['title']); ?>
                        </h3>
                    <?php endif; ?>

                    <?php
                    if (!empty($option['fields'])) {
                        foreach ($option['fields'] as $field) {
                            // Delegate field rendering to the framework so markup stays consistent.
                            $framework->render($field);
                        }
                    }
                    ?>
                </section>
                <?php
                $counter++;
            endforeach;
            ?>
        </div>
    </div>
</form>
