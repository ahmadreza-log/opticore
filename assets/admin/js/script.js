/**
 * OptiCore admin behaviour script.
 *
 * This file enhances the PHP-rendered settings screen with:
 * - left-hand section navigation (with hash + localStorage persistence),
 * - an AJAX-powered settings form (with a loading state for the submit button),
 * - a lightweight toast notification system, and
 * - dynamic show/hide behaviour for fields with dependencies. ⚡
 *
 * All logic is wrapped in an IIFE that receives `jQuery` to avoid polluting the
 * global scope while still using the familiar `$` shorthand.
 */
; (function ($) {
    'use strict'

    /**
     * Centralised selectors for DOM queries. Keeping them grouped avoids
     * scattering raw selectors throughout the codebase and makes refactoring
     * easier if the admin markup changes.
     */
    const Selectors = {
        MenuItem: '.settings-menu-item',
        Section: '.settings-menu-section',
        Form: 'form.opticore-settings-form',
        PagespeedApiKeyForm: 'form.pagespeed-api-key',
        Field: (Id) => `[name="opticore-setting-${Id}"]`,
        DependencyRow: '[data-dependency-config], [data-dependency-field]',
    }

    /**
     * Keys used for localStorage persistence.
     */
    const StorageKeys = {
        ActiveSection: 'opticoreActiveSection',
    }

    /**
     * Minimal toast helper for surfacing AJAX responses to the user.
     */
    const Toast = {
        ContainerId: 'opticore-toast-stack',
        StyleId: 'opticore-toast-styles',
        Show(Message, Status = 'success') {
            this.EnsureStyles()

            let $Stack = $(`#${this.ContainerId}`)

            if (!$Stack.length) {
                $Stack = $(
                    `<div id="${this.ContainerId}" class="opticore-toast-stack"></div>`
                )
                $('body').append($Stack)
            }

            const $ToastElement = $(
                `<div class="opticore-toast opticore-toast--${Status}">${Message}</div>`
            )

            $Stack.append($ToastElement)
            requestAnimationFrame(() => $ToastElement.addClass('is-visible'))

            setTimeout(() => {
                $ToastElement.removeClass('is-visible')
                setTimeout(() => $ToastElement.remove(), 250)
            }, 3500)
        },
        EnsureStyles() {
            if ($(`#${this.StyleId}`).length) {
                return
            }

            const Styles = `
                #${this.ContainerId} {
                    position: fixed;
                    bottom: 1.5rem;
                    right: 1.5rem;
                    z-index: 10000;
                    display: flex;
                    flex-direction: column;
                    gap: 0.75rem;
                }
                .opticore-toast {
                    min-width: 220px;
                    max-width: 320px;
                    padding: 0.875rem 1.25rem;
                    border-radius: 0.75rem;
                    box-shadow: 0 10px 25px -15px rgba(15, 23, 42, 0.6);
                    font-size: 0.925rem;
                    font-weight: 500;
                    color: #FFFFFF;
                    background: #8E51FF;
                    background: linear-gradient(135deg, rgba(142, 81, 255, 1) 0%, rgba(0, 188, 255, 1) 100%);
                    opacity: 0;
                    transform: translateY(10px);
                    transition: opacity 0.2s ease, transform 0.2s ease;
                }
                .opticore-toast.is-visible {
                    opacity: 1;
                    transform: translateY(0);
                }
                .opticore-toast--success {
                    background: #9AE600;
                    background: linear-gradient(135deg,rgba(154, 230, 0, 1) 0%, rgba(0, 188, 125, 1) 100%);
                }
                .opticore-toast--error {
                    background: #FF6467;
                    background: linear-gradient(135deg,rgba(255, 100, 103, 1) 0%, rgba(159, 7, 18, 1) 100%);
                }
            `

            $('head').append(`<style id="${this.StyleId}">${Styles}</style>`)
        },
    }

    const Messages = (() => {
        const Defaults = {
            Saved: 'Changes saved successfully.',
            Error: 'Something went wrong. Please try again.',
            Saving: 'Saving…',
        }

        if (!window.opticore || !window.opticore.i18n) {
            return Defaults
        }

        return {
            Saved: window.opticore.i18n.saved ?? Defaults.Saved,
            Error: window.opticore.i18n.error ?? Defaults.Error,
            Saving: window.opticore.i18n.saving ?? Defaults.Saving,
        }
    })()

    /**
     * Retrieve the most recently visited settings section from storage.
     */
    function ReadActiveSectionFromStorage() {
        try {
            return window.localStorage.getItem(StorageKeys.ActiveSection) || ''
        } catch (Error) {
            return ''
        }
    }

    /**
     * Persist the selected settings section for future visits.
     */
    function WriteActiveSectionToStorage(Target) {
        try {
            window.localStorage.setItem(StorageKeys.ActiveSection, Target)
        } catch (Error) {
            // Ignore quota errors or private mode restrictions
        }
    }

    /**
     * Extract the section slug from a menu anchor.
     */
    function ResolveTargetFromMenu($MenuItem) {
        const Hash = ($MenuItem.attr('href') || '').split('#')[1]
        return Hash || ''
    }

    /**
     * Look up the menu item matching a provided section hash.
     */
    function FindMenuItemByTarget(Target) {
        return $(Selectors.MenuItem).filter(function () {
            return ResolveTargetFromMenu($(this)) === Target
        })
    }

    /**
     * Show the requested section, update menu states, and optionally animate.
     */
    function ActivateSection(Target, ShouldAnimate = false) {
        if (!Target) {
            return
        }

        const SectionSelector = `#section-${Target}`
        const $Section = $(SectionSelector)

        if (!$Section.length) {
            return
        }

        const $MenuItem = FindMenuItemByTarget(Target)

        $(Selectors.MenuItem).removeClass('active')
        if ($MenuItem.length) {
            $MenuItem.addClass('active')
        }

        $(Selectors.Section).hide()
        if (ShouldAnimate) {
            $Section.fadeIn('fast')
        } else {
            $Section.show()
        }

        if (Target) {
            window.location.hash = Target
        }
    }

    /**
     * Restore the default section on load using URL hash → storage → fallback order.
     */
    function RestoreInitialSection() {
        const HashTarget = (window.location.hash || '').replace('#', '')
        const StoredTarget = ReadActiveSectionFromStorage()
        const $FirstMenuItem = $(Selectors.MenuItem).first()
        const DefaultTarget = ResolveTargetFromMenu($FirstMenuItem)

        const Target = HashTarget || StoredTarget || DefaultTarget

        if (Target) {
            ActivateSection(Target, false)
        }
    }

    /**
     * Inject lightweight CSS that gives buttons a loading state during saves.
     */
    function EnsureButtonStateStyles() {
        const StyleId = 'opticore-button-state-styles'

        if ($(`#${StyleId}`).length) {
            return
        }

        const Styles = `
            .opticore-button--processing {
                opacity: 0.6;
                cursor: not-allowed !important;
            }
            .opticore-button--processing svg {
                animation: opticore-spin 0.8s linear infinite;
            }
            @keyframes opticore-spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            .opticore-button--processing-content {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
            }
        `

        $('head').append(`<style id="${StyleId}">${Styles}</style>`)
    }

    /**
     * HTML markup used for the loading indicator shown within the submit button.
     */
    function BuildLoadingButtonMarkup() {
        return `
            <span class="opticore-button--processing-content">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 32 32" aria-hidden="true">
                    <path fill="currentColor" d="M16 4c-5.113 0-9.383 3.16-11.125 7.625l1.844.75C8.176 8.641 11.71 6 16 6c3.242 0 6.133 1.59 7.938 4H20v2h7V5h-2v3.094A11.94 11.94 0 0 0 16 4m9.281 15.625C23.824 23.359 20.29 26 16 26c-3.277 0-6.156-1.613-7.969-4H12v-2H5v7h2v-3.094C9.188 26.386 12.395 28 16 28c5.113 0 9.383-3.16 11.125-7.625z" />
                </svg>
                <span>${Messages.Saving}</span>
            </span>
        `
    }

    /**
     * Disable the submit button and swap the contents for a spinner + text.
     */
    function ActivateSubmitButtonState($Button) {
        if (!$Button.length) {
            return
        }

        if (!$Button.data('OriginalHtml')) {
            $Button.data('OriginalHtml', $Button.html())
        }

        $Button
            .prop('disabled', true)
            .attr('aria-disabled', 'true')
            .addClass('opticore-button--processing pointer-events-none')
            .html(BuildLoadingButtonMarkup())
    }

    /**
     * Restore the submit button to its original state after an AJAX request finishes.
     */
    function RestoreSubmitButtonState($Button) {
        if (!$Button.length) {
            return
        }

        const OriginalHtml = $Button.data('OriginalHtml')

        if (OriginalHtml !== undefined) {
            $Button.html(OriginalHtml)
        }

        $Button
            .prop('disabled', false)
            .attr('aria-disabled', 'false')
            .removeClass('opticore-button--processing pointer-events-none')
    }

    /**
     * Attach click handlers to the left-hand navigation.
     */
    function InitMenu() {
        $(Selectors.MenuItem).on('click', function (Event) {
            Event.preventDefault()

            const Target = ResolveTargetFromMenu($(this))

            ActivateSection(Target, true)
            WriteActiveSectionToStorage(Target)
        })
    }

    /**
     * Submit the settings form over AJAX and surface toast notifications.
     */
    function HandleFormSubmit(Event) {
        Event.preventDefault()

        const $Form = $(Event.currentTarget)
        const $SubmitButton = $Form.find('[type="submit"]')
        const FormData = $Form.serialize()

        ActivateSubmitButtonState($SubmitButton)

        $.ajax({
            url: opticore.ajax,
            type: 'GET',
            data: FormData,
        })
            .done((Response) => {
                console.log(Response)
                const Message =
                    Response &&
                        Response.data &&
                        Response.data.message
                        ? Response.data.message
                        : Messages.Saved

                Toast.Show(Message, 'success')
            })
            .fail(() => {
                Toast.Show(Messages.Error, 'error')
            })
            .always(() => {
                RestoreSubmitButtonState($SubmitButton)
            })
    }

    /**
     * Bind AJAX submission behaviour to the settings form.
     */
    function InitForm() {
        $(Selectors.Form).on('submit', HandleFormSubmit)
        $(Selectors.PagespeedApiKeyForm).on('submit', HandleFormSubmit)
    }

    /**
     * Retrieve the value for a dependency field, handling checkboxes specially.
     */
    function ResolveInputValue(FieldId) {
        const $Input = $(Selectors.Field(FieldId))

        if (!$Input.length) {
            return ''
        }

        const Type = ($Input.attr('type') || '').toLowerCase()

        if (Type === 'checkbox') {
            return $Input.is(':checked') ? '1' : ''
        }

        return $Input.val() ?? ''
    }

    /**
     * Convert a comma-delimited list into an array when dependency values allow multiple matches.
     */
    function ParseDependencyValue(Value) {
        if (Array.isArray(Value)) {
            return Value
        }

        if (typeof Value !== 'string' || Value.indexOf(',') === -1) {
            return Value
        }

        return Value.split(',').map((Item) => Item.trim())
    }

    /**
     * Normalise the dependency configuration stored on a row.
     */
    function NormalizeDependencyConfig($Row) {
        const Cached = $Row.data('opticoreDependencyConfig')

        if (Cached) {
            return Cached
        }

        const RawConfig = $Row.data('dependencyConfig')

        if (RawConfig && typeof RawConfig === 'object') {
            const Relation =
                typeof RawConfig.relation === 'string'
                    ? RawConfig.relation.toUpperCase()
                    : 'AND'

            const RawConditions = Array.isArray(RawConfig.conditions)
                ? RawConfig.conditions
                : RawConfig.conditions
                    ? [RawConfig.conditions]
                    : []

            const Conditions = RawConditions.map(NormalizeDependencyCondition).filter(
                Boolean
            )

            if (Conditions.length) {
                const Config = {
                    relation: Relation === 'OR' ? 'OR' : 'AND',
                    conditions: Conditions,
                }

                $Row.data('opticoreDependencyConfig', Config)
                return Config
            }
        }

        const LegacyField = $Row.data('dependency-field')

        if (!LegacyField) {
            return null
        }

        const LegacyOperator = $Row.data('dependency-operator') || '=='
        const LegacyValue = ParseDependencyValue($Row.data('dependency-value'))

        const Config = {
            relation: 'AND',
            conditions: [
                NormalizeDependencyCondition([
                    LegacyField,
                    LegacyOperator,
                    LegacyValue,
                ]),
            ],
        }

        $Row.data('opticoreDependencyConfig', Config)
        return Config
    }

    /**
     * Convert a raw condition (object or array) into a standard structure.
     */
    function NormalizeDependencyCondition(Condition) {
        if (!Condition || typeof Condition !== 'object') {
            return null
        }

        let Field =
            Condition.field ??
            Condition.id ??
            (Array.isArray(Condition) ? Condition[0] : undefined)
        let Operator =
            Condition.operator ??
            (Array.isArray(Condition) ? Condition[1] : undefined) ??
            '=='
        let Value =
            Condition.value ??
            (Array.isArray(Condition) ? Condition[2] : undefined) ??
            ''

        if (!Field) {
            return null
        }

        if (typeof Operator !== 'string' || !Operator.length) {
            Operator = '=='
        }

        return {
            field: String(Field),
            operator: Operator,
            value: ParseDependencyValue(Value),
        }
    }

    /**
     * Evaluate whether a dependency constraint is currently satisfied.
     */
    function DoesDependencyMatch(Config) {
        if (!Config || !Array.isArray(Config.conditions)) {
            return true
        }

        const Relation = Config.relation === 'OR' ? 'OR' : 'AND'
        const Evaluations = Config.conditions.map(EvaluateDependencyCondition)

        if (Relation === 'OR') {
            return Evaluations.some(Boolean)
        }

        return Evaluations.every(Boolean)
    }

    /**
     * Evaluate a single condition against the current field values.
     */
    function EvaluateDependencyCondition(Condition) {
        const FieldId = Condition.field

        if (!FieldId) {
            return true
        }

        const Operator = Condition.operator || '=='
        const Expected = Condition.value
        const Current = ResolveInputValue(FieldId)

        const ExpectedValues = Array.isArray(Expected)
            ? Expected.map(String)
            : [String(Expected)]

        switch (Operator) {
            case '!=':
            case '!==':
                if (Array.isArray(Expected)) {
                    return !ExpectedValues.includes(String(Current))
                }

                return String(Current) !== String(Expected)
            case 'in': {
                const List = Array.isArray(Expected) ? Expected : [Expected]
                return List.map(String).includes(String(Current))
            }
            case 'not_in': {
                const List = Array.isArray(Expected) ? Expected : [Expected]
                return !List.map(String).includes(String(Current))
            }
            case '==':
            case '===':
            default:
                if (Array.isArray(Expected)) {
                    return ExpectedValues.includes(String(Current))
                }

                return String(Current) === String(Expected)
        }
    }

    /**
     * Toggle dependency-blocked rows based on the controlling input field.
     */
    function RefreshDependencies(FieldId, DependencyMap, $Rows) {
        const Rows = FieldId ? DependencyMap[FieldId] || [] : $Rows.get()
        const UniqueRows = Array.from(new Set(Rows))

        $(UniqueRows).each(function () {
            const $Row = $(this)
            const Config = NormalizeDependencyConfig($Row)

            if (!Config) {
                return
            }

            const IsVisible = DoesDependencyMatch(Config)

            if (IsVisible) {
                $Row.stop(true, true).slideDown('fast')
            } else {
                $Row.stop(true, true).slideUp('fast')
            }
        })
    }

    /**
     * Observe dependency-triggering inputs and perform the initial pass to toggle visibility.
     */
    function InitDependencies() {
        const $Rows = $(Selectors.DependencyRow).filter(function () {
            return NormalizeDependencyConfig($(this)) !== null
        })

        if (!$Rows.length) {
            return
        }

        const DependencyMap = {}

        $Rows.each(function () {
            const $Row = $(this)
            const Config = NormalizeDependencyConfig($Row)

            if (!Config) {
                return
            }

            Config.conditions.forEach((Condition) => {
                const FieldId = Condition.field

                if (!FieldId) {
                    return
                }

                DependencyMap[FieldId] = DependencyMap[FieldId] || []

                if (!DependencyMap[FieldId].includes($Row[0])) {
                    DependencyMap[FieldId].push($Row[0])
                }
            })
        })

        Object.keys(DependencyMap).forEach((FieldId) => {
            const $Input = $(Selectors.Field(FieldId))

            if (!$Input.length) {
                return
            }

            const TriggerEvents = $Input.is(':checkbox') ? 'change' : 'input change'

            $Input.on(TriggerEvents, () =>
                RefreshDependencies(FieldId, DependencyMap, $Rows)
            )
        })

        RefreshDependencies(undefined, DependencyMap, $Rows)
    }

    function InitTooltip() {
        $('[data-tooltip]').each(function () {
            const selector = $(this)
            const text = selector.data('tooltip')

            let options = selector.data()
            delete options['tooltip']

            let id = 'tooltip-' + Math.random().toString(36).substring(2, 15)

            selector.attr('data-tooltip-id', id)
            selector.hover(function () {
                $('body').append(`<div class="tooltip" id="${id}" data-show="true">${text}<div id="${id}-arrow" class="tooltip-arrow" data-popper-arrow></div></div>`)
                Popper.createPopper(document.querySelector(`[data-tooltip-id="${id}" ]`), document.querySelector(`#${id}`), {
                    placement: options.placement || 'top',
                    strategy: options.strategy || 'fixed',
                    modifiers: options.modifiers || [
                        {
                            name: 'preventOverflow',
                            options: {
                                altBoundary: true,
                                padding: 8,
                            }
                        }, {
                            name: 'offset',
                            options: {
                                offset: [0, 8],
                            },
                        },
                    ]
                })
            }, function () {
                $(`#${id}`).remove()
            })


        })
    }

    /**
     * Entry point executed on document ready.
     */
    function Initialize() {
        EnsureButtonStateStyles()
        InitMenu()
        RestoreInitialSection()
        InitForm()
        InitDependencies()
        InitTooltip()
    }

    $(Initialize)
})(jQuery)