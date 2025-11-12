<?php

namespace Opticore;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lightweight settings framework powering the OptiCore admin UI.
 *
 * The class stores field metadata, resolves dependencies, and renders input
 * controls. Snippets registered under `includes/functions` consume the stored
 * options to enable/disable optimisations.
 */
class Framework
{

    public $options = [];

    /**
     * Cached settings loaded from the database.
     *
     * @var array<string, mixed>
     */
    protected array $settings = [];

    /**
     * Registry of field definitions, used for dependency lookups.
     *
     * @var array<string, array<mixed>>
     */
    protected array $fields = [];

    public function __construct()
    {
        // Preload stored option values so repeated calls are cheap.
        $this->settings = $this->hydrate();
    }

    /**
     * Return the framework sections and fields.
     */
    public function options(): array
    {
        $this->fields = [];

        $sections = $this->sections();

        return apply_filters('opticore-framework-options', $sections);
    }

    /**
     * Render a field row within the settings screen.
     */
    public function render(array $field): void
    {
        if (empty($field['id'])) {
            return;
        }

        $fieldId = (string) $field['id'];
        $value = $this->value($fieldId, $field);
        $isVisible = $this->visible($field);
        $attributes = $this->dependency($field);
        $style = $isVisible ? '' : ' style="display:none;"';

        echo '<div class="flex gap-6 py-6 border-zinc-300" data-field-id="' . esc_attr($fieldId) . '"' . $attributes . $style . '>';

        if (!empty($field['title'])) {
            echo '<h4 class="mt-0! w-40">' . esc_html($field['title']) . '</h4>';
        }

        echo '<div class="flex flex-col gap-4">';

        $this->input($field, $fieldId, $value);

        if (!empty($field['description'])) {
            echo '<div class="text-zinc-500">' . wp_kses_post($field['description']) . '</div>';
        }

        echo '</div>';
        echo '</div>';
    }

    /**
     * Load saved settings from the database.
     */
    protected function hydrate(): array
    {
        $stored = get_option('opticore-settings', []);

        return is_array($stored) ? $stored : [];
    }

    /**
     * Build the base section configuration.
     */
    protected function sections(): array
    {
        return [
            [
                'id' => 'general',
                'title' => __('General', 'opticore'),
                'icon' => 'settings',
                // Each field definition corresponds to a PHP optimisation file.
                'fields' => $this->collect([
                    [
                        'id' => 'disable-emojis',
                        'title' => __('Disable Emojis', 'opticore'),
                        'description' => __('Remove emoji detection scripts and styles from WordPress.', 'opticore'),
                        'type' => 'switcher',
                    ],
                    [
                        'id' => 'disable-dashicons',
                        'title' => __('Disable Dashicons', 'opticore'),
                        'description' => __('Disable Dashicons on frontend.', 'opticore'),
                        'type' => 'switcher',
                    ],
                    [
                        'id' => 'disable-embeds',
                        'title' => __('Disable Embeds', 'opticore'),
                        'description' => __('Disable WordPress oEmbed functionality and scripts.', 'opticore'),
                        'type' => 'switcher',
                    ],
                    [
                        'id' => 'disable-shortlink',
                        'title' => __('Disable Shortlink', 'opticore'),
                        'description' => __('Disable shortlink meta tag from head section.', 'opticore'),
                        'type' => 'switcher',
                    ],
                    [
                        'id' => 'disable-rss-feeds',
                        'title' => __('Disable RSS Feeds', 'opticore'),
                        'description' => __('Disable WordPress generated RSS feeds and 301 redirect URL to parent.', 'opticore'),
                        'type' => 'switcher',
                    ],
                    [
                        'id' => 'disable-self-pingback',
                        'title' => __('Disable Self Pingback', 'opticore'),
                        'description' => __('Disable self-pingback to reduce server load.', 'opticore'),
                        'type' => 'switcher',
                    ],
                    [
                        'id' => 'disable-comments',
                        'title' => __('Disable Comments', 'opticore'),
                        'description' => __('Completely disable comments functionality. This will also disable comment URLs from admin panel.', 'opticore'),
                        'type' => 'switcher',
                    ],
                    [
                        'id' => 'disable-comment-urls',
                        'title' => __('Disable Comment URLs', 'opticore'),
                        'description' => __('Disable comment URLs from blog posts.', 'opticore'),
                        'type' => 'switcher',
                        'dependency' => ['disable-comments', '!==', '1'],
                    ],
                    [
                        'id' => 'add-blank-favicon',
                        'title' => __('Add Blank Favicon', 'opticore'),
                        'description' => __('Add a blank favicon to your WordPress header, which will prevent a missing favicon or 404 error. If you already have a favicon on your site, you should leave this off.', 'opticore'),
                        'type' => 'switcher',
                    ],
                    [
                        'id' => 'disable-heartbeat',
                        'title' => __('Disable Heartbeat', 'opticore'),
                        'description' => __('Disable WordPress heartbeat API.', 'opticore'),
                        'type' => 'dropdown',
                        'options' => [
                            'default' => __('Default', 'opticore'),
                            'disable' => __('Disable', 'opticore'),
                            'only-editing' => __('Only When Editing Posts/Pages', 'opticore'),
                        ],
                    ],
                    [
                        'id' => 'heartbeat-frequency',
                        'title' => __('Heartbeat Frequency', 'opticore'),
                        'description' => __('Set the heartbeat frequency in seconds.', 'opticore'),
                        'type' => 'number',
                        'min' => 10,
                        'max' => 120,
                        'step' => 1,
                        'default' => 60,
                        'dependency' => ['disable-heartbeat', '!==', 'disable'],
                    ],
                    [
                        'id' => 'limit-post-revisions',
                        'title' => __('Limit Post Revisions', 'opticore'),
                        'description' => __('Limit the number of post revisions to a maximum of 5.', 'opticore'),
                        'type' => 'number',
                        'min' => -1,
                        'max' => 9999,
                        'step' => 1,
                        'default' => 5,
                    ],
                    [
                        'id' => 'autosave-interval',
                        'title' => __('Autosave Interval', 'opticore'),
                        'description' => __('Set the autosave interval in seconds.', 'opticore'),
                        'type' => 'number',
                        'min' => 60,
                        'max' => 600,
                        'step' => 10,
                        'default' => 60,
                    ],
                ]),
            ],
            [
                'id' => 'html',
                'title' => __('HTML', 'opticore'),
                'icon' => 'html',
                'fields' => $this->collect([
                    [
                        'id' => 'remove-html-comments',
                        'title' => __('Remove HTML Comments', 'opticore'),
                        'description' => __('Remove HTML comments from the HTML head.', 'opticore'),
                        'type' => 'switcher',
                    ],
                    [
                        'id' => 'minify-html',
                        'title' => __('Minify HTML', 'opticore'),
                        'description' => __('Minify HTML to reduce file size and improve load times.', 'opticore'),
                        'type' => 'switcher',
                    ],
                ])
            ],
            [
                'id' => 'css',
                'title' => __('CSS', 'opticore'),
                'icon' => 'css',
                'fields' => $this->collect([
                    [
                        'id' => 'remove-global-styles',
                        'title' => __('Remove Global Styles', 'opticore'),
                        'description' => __('Remove the inline global styles related to WordPress core blocks.', 'opticore'),
                        'type' => 'switcher',
                    ],
                    [
                        'id' => 'separate-block-styles',
                        'title' => __('Separate Block Styles', 'opticore'),
                        'description' => __('Load core block styles only when they are rendered instead of in a global stylesheet.', 'opticore'),
                        'type' => 'switcher',
                    ],
                    [
                        'id' => 'minify-css',
                        'title' => __('Minify CSS', 'opticore'),
                        'description' => __('Minify CSS files to reduce file size and improve load times.', 'opticore'),
                        'type' => 'switcher',
                    ],
                    [
                        'id' => 'exclude-css',
                        'title' => __('Exclude CSS', 'opticore'),
                        'description' => __('Exclude specific CSS files from minification and combination by adding the source URL (example.css). Format: one per line.', 'opticore'),
                        'type' => 'textarea',
                        'placeholder' => sprintf(__('Example: %s', 'opticore'), "\nhttps://example.com/style.css\n" . get_stylesheet_uri()),
                        'dependency' => [
                            'relation' => 'OR',
                            'conditions' => [
                                ['field' => 'minify-css', 'operator' => '==', 'value' => '1'],
                                ['field' => 'combine-css', 'operator' => '==', 'value' => '1'],
                            ],
                        ],
                    ],
                    [
                        'id' => 'output-type-css',
                        'title' => __('Output Type', 'opticore'),
                        'description' => __('Output CSS in the head section of the HTML document.', 'opticore'),
                        'type' => 'dropdown',
                        'options' => [
                            'file' => __('File', 'opticore'),
                            'internal' => __('Internal', 'opticore'),
                        ],
                        'default' => 'file',
                    ],
                ])
            ],
            [
                'id' => 'javascript',
                'title' => __('JavaScript', 'opticore'),
                'icon' => 'javascript',
                'fields' => $this->collect([
                    [
                        'id' => 'remove-jquery-migrate',
                        'title' => __('Remove jQuery Migrate', 'opticore'),
                        'description' => __('Remove jQuery Migrate script if not needed by your plugins.', 'opticore'),
                        'type' => 'switcher',
                    ],
                ])
            ],
            [
                'id' => 'media',
                'title' => __('Assets & Media', 'opticore'),
                'icon' => 'animated_images',
            ],
            [
                'id' => 'security',
                'title' => __('Security', 'opticore'),
                'icon' => 'shield',
                'fields' => $this->collect([
                    [
                        'id' => 'hide-wp-version',
                        'title' => __('Hide WP Version', 'opticore'),
                        'description' => __('Remove WordPress version meta tag.', 'opticore'),
                        'type' => 'switcher',
                    ],
                    [
                        'id' => 'disable-xml-rpc',
                        'title' => __('Disable XML-RPC', 'opticore'),
                        'description' => __('Disable XML-RPC for better security (may break mobile apps).', 'opticore'),
                        'type' => 'switcher',
                    ],
                    [
                        'id' => 'disable-rest-api',
                        'title' => __('REST API Access', 'opticore'),
                        'description' => __('Disable WordPress REST API.', 'opticore'),
                        'type' => 'dropdown',
                        'options' => [
                            'active' => __('Active', 'opticore'),
                            'admin-only' => __('Only For Administrator', 'opticore'),
                            'disable' => __('Disable', 'opticore'),
                        ],
                    ],
                    [
                        'id' => 'remove-rest-api-link',
                        'title' => __('Remove REST API Link', 'opticore'),
                        'description' => __('Remove REST API link from head. API still works if accessed directly.', 'opticore'),
                        'type' => 'switcher',
                        'dependency' => ['disable-rest-api', '!==', 'disable'],
                    ]
                ]),
            ],
        ];
    }

    /**
     * Register fields while keeping access to their definitions.
     *
     * @param array<int, array<mixed>> $fields
     * @return array<int, array<mixed>>
     */
    protected function collect(array $fields): array
    {
        return array_map(fn($field) => $this->store($field), $fields);
    }

    /**
     * Store a field definition for use during rendering.
     *
     * @param array<mixed> $field
     * @return array<mixed>
     */
    protected function store(array $field): array
    {
        if (!empty($field['id'])) {
            $this->fields[$field['id']] = $field;
        }

        return $field;
    }

    /**
     * Resolve the current value for a field.
     *
     * @param string $id
     * @param array<mixed>|null $field
     */
    protected function value(string $id, ?array $field = null)
    {
        if ($id === '') {
            return null;
        }

        if (array_key_exists($id, $this->settings)) {
            $stored = $this->settings[$id];

            if ($stored !== '') {
                return $stored;
            }
        }

        $fieldData = $field ?? ($this->fields[$id] ?? null);

        if (is_array($fieldData) && array_key_exists('default', $fieldData)) {
            return $fieldData['default'];
        }

        return null;
    }

    /**
     * Determine whether a field should be visible based on dependencies.
     *
     * @param array<mixed> $field
     */
    protected function visible(array $field): bool
    {
        $config = $this->normalizeDependency($field['dependency'] ?? null);

        if ($config === null) {
            return true;
        }

        $relation = $config['relation'];
        $conditions = $config['conditions'];

        if ($relation === 'OR') {
            foreach ($conditions as $condition) {
                if ($this->evaluateDependencyCondition($condition)) {
                    return true;
                }
            }

            return false;
        }

        foreach ($conditions as $condition) {
            if (!$this->evaluateDependencyCondition($condition)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Add dependency attributes to the field wrapper for JS toggling.
     *
     * @param array<mixed> $field
     */
    protected function dependency(array $field): string
    {
        $config = $this->normalizeDependency($field['dependency'] ?? null);

        if ($config === null) {
            return '';
        }

        $encoded = wp_json_encode($config);

        if ($encoded === false) {
            return '';
        }

        $attributes = sprintf(
            ' data-dependency-config="%s"',
            esc_attr($encoded)
        );

        if (count($config['conditions']) === 1) {
            $condition = $config['conditions'][0];
            $value = $condition['value'];
            $valueString = is_array($value) ? implode(',', array_map('strval', $value)) : (string) $value;

            $attributes .= sprintf(
                ' data-dependency-field="%s" data-dependency-operator="%s" data-dependency-value="%s"',
                esc_attr($condition['field']),
                esc_attr($condition['operator']),
                esc_attr($valueString)
            );
        }

        return $attributes;
    }

    /**
     * Determine whether stored value should be treated as truthy.
     */
    protected function truthy($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
        }

        return !empty($value);
    }

    /**
     * Render the field input element based on its type.
     *
     * @param array<mixed> $field
     * @param mixed $value
     */
    protected function input(array $field, string $fieldId, $value): void
    {
        $type = $field['type'] ?? 'text';

        switch ($type) {
            case 'switcher':
            case 'checkbox':
                $checked = $this->truthy($value) ? ' checked' : '';

                echo '<label class="flex cursor-pointer">';
                echo '<input type="checkbox" class="hidden! peer" name="opticore-setting-' . esc_attr($fieldId) . '" value="1"' . $checked . ' />';

                $activeColor = $type === 'switcher' ? 'peer-checked:bg-sky-400' : 'peer-checked:bg-emerald-400';
                echo '<span class="bg-zinc-300 w-15 h-6 rounded-full relative before:bg-white before:w-5 before:h-5 before:absolute before:top-0.5 peer-not-checked:before:left-0.5 before:shadow-lg before:rounded-full ' . $activeColor . ' peer-checked:before:left-full peer-checked:before:-translate-x-5.5 transition-bg duration-300 before:transition-bg before:duration-300"></span>';
                echo '</label>';
                break;

            case 'dropdown':
                echo '<select class="opticore-input-select w-80! py-2! border-2! border-zinc-300! rounded-lg! focus:border-sky-400! focus:ring-sky-400! focus:ring-0! focus:text-zinc-500! text-zinc-500!" name="opticore-setting-' . esc_attr($fieldId) . '">';

                if (!empty($field['options']) && is_array($field['options'])) {
                    foreach ($field['options'] as $optionValue => $optionLabel) {
                        if (is_int($optionValue)) {
                            $optionValue = $optionLabel;
                        }

                        echo '<option value="' . esc_attr($optionValue) . '"' . selected((string) $value, (string) $optionValue, false) . '>' . esc_html($optionLabel) . '</option>';
                    }
                }

                echo '</select>';
                break;

            case 'number':
                $numberValue = $value ?? ($field['default'] ?? '');
                $min = isset($field['min']) ? ' min="' . esc_attr($field['min']) . '"' : '';
                $max = isset($field['max']) ? ' max="' . esc_attr($field['max']) . '"' : '';
                $step = isset($field['step']) ? ' step="' . esc_attr($field['step']) . '"' : '';

                echo '<input type="number" class="opticore-input-number w-80! py-2! border-2! border-zinc-300! rounded-lg! focus:border-sky-400! focus:ring-sky-400! focus:ring-0! focus:text-zinc-500! text-zinc-500!" name="opticore-setting-' . esc_attr($fieldId) . '" value="' . esc_attr($numberValue) . '"' . $min . $max . $step . '>';
                break;

            case 'textarea':
                $textareaValue = $value ?? ($field['default'] ?? '');

                echo '<textarea class="opticore-input-textarea w-full! h-40! py-2! border-2! border-zinc-300! rounded-lg! focus:border-sky-400! focus:ring-sky-400! focus:ring-0! focus:text-zinc-500! text-zinc-500!" row="20" name="opticore-setting-' . esc_attr($fieldId) . '" placeholder="' . esc_attr($field['placeholder'] ?? '') . '">' . esc_textarea($textareaValue) . '</textarea>';
                break;

            case 'text':
            default:
                $textValue = $value ?? ($field['default'] ?? '');

                echo '<input type="text" class="opticore-input-text w-80! py-2! border-2! border-zinc-300! rounded-lg! focus:border-sky-400! focus:ring-sky-400! focus:ring-0! focus:text-zinc-500! text-zinc-500!" name="opticore-setting-' . esc_attr($fieldId) . '" value="' . esc_attr($textValue) . '">';
                break;
        }
    }

    /**
     * Normalise dependency definitions to a consistent structure.
     *
     * @param mixed $dependency
     * @return array{relation: string, conditions: array<int, array{field: string, operator: string, value: mixed}>}|null
     */
    protected function normalizeDependency($dependency): ?array
    {
        if (empty($dependency)) {
            return null;
        }

        $relation = 'AND';
        $conditions = [];

        if (is_array($dependency) && isset($dependency['conditions'])) {
            $relationCandidate = strtoupper((string) ($dependency['relation'] ?? 'AND'));
            $relation = in_array($relationCandidate, ['AND', 'OR'], true) ? $relationCandidate : 'AND';
            $rawConditions = $dependency['conditions'];

            if (!$this->isList($rawConditions)) {
                $rawConditions = [$rawConditions];
            }

            foreach ($rawConditions as $condition) {
                $normalized = $this->normalizeDependencyCondition($condition);

                if ($normalized !== null) {
                    $conditions[] = $normalized;
                }
            }
        } elseif (is_array($dependency)) {
            if (isset($dependency['relation'])) {
                $relationCandidate = strtoupper((string) $dependency['relation']);
                $relation = in_array($relationCandidate, ['AND', 'OR'], true) ? $relationCandidate : 'AND';
                unset($dependency['relation']);
            }

            if ($this->isList($dependency) && isset($dependency[0]) && is_array($dependency[0])) {
                foreach ($dependency as $condition) {
                    $normalized = $this->normalizeDependencyCondition($condition);

                    if ($normalized !== null) {
                        $conditions[] = $normalized;
                    }
                }
            } else {
                $normalized = $this->normalizeDependencyCondition($dependency);

                if ($normalized !== null) {
                    $conditions[] = $normalized;
                }
            }
        }

        if (empty($conditions)) {
            return null;
        }

        return [
            'relation' => $relation,
            'conditions' => $conditions,
        ];
    }

    /**
     * Standardise a single dependency condition into an associative array.
     *
     * @param mixed $condition
     * @return array{field: string, operator: string, value: mixed}|null
     */
    protected function normalizeDependencyCondition($condition): ?array
    {
        if (!is_array($condition)) {
            return null;
        }

        $field = $condition['field'] ?? ($condition['id'] ?? ($condition[0] ?? null));
        $operator = $condition['operator'] ?? ($condition[1] ?? '==');
        $value = $condition['value'] ?? ($condition[2] ?? '');

        if ($field === null || $field === '') {
            return null;
        }

        if (!is_string($operator) || $operator === '') {
            $operator = '==';
        }

        return [
            'field' => (string) $field,
            'operator' => $operator,
            'value' => $value,
        ];
    }

    /**
     * Determine if the provided array has sequential integer keys.
     */
    protected function isList(array $array): bool
    {
        return $array === [] || array_keys($array) === range(0, count($array) - 1);
    }

    /**
     * Evaluate an individual dependency condition against stored values.
     *
     * @param array{field: string, operator: string, value: mixed} $condition
     */
    protected function evaluateDependencyCondition(array $condition): bool
    {
        $field = (string) ($condition['field'] ?? '');

        if ($field === '') {
            return true;
        }

        $operator = (string) ($condition['operator'] ?? '==');
        $expected = $condition['value'] ?? '';
        $current = $this->value($field);

        $currentValues = is_array($current) ? array_map('strval', $current) : [(string) $current];
        $expectedValues = is_array($expected) ? array_map('strval', $expected) : [(string) $expected];
        $currentValue = $currentValues[0] ?? '';
        $expectedValue = $expectedValues[0] ?? '';

        switch ($operator) {
            case '!=':
            case '!==':
                if (is_array($expected)) {
                    return count(array_intersect($currentValues, $expectedValues)) === 0;
                }

                return $currentValue !== $expectedValue;

            case 'in':
                return count(array_intersect($currentValues, $expectedValues)) > 0;

            case 'not_in':
                return count(array_intersect($currentValues, $expectedValues)) === 0;

            case '==':
            case '===':
            default:
                if (is_array($expected)) {
                    return count(array_intersect($currentValues, $expectedValues)) > 0;
                }

                return $currentValue === $expectedValue;
        }
    }
}
