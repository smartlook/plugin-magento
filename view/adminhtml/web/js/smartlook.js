require(
    ['jquery'],
    function ($) {
        $(
            function () {
                var lastOpen = null;

                function openForm(type)
                {
                    lastOpen = type;
                    updateTexts(type);
                    updateClass(type);
                    $('#home').hide();
                    $('#connect').show();
                    $('.js-clear').hide();
                }

                function closeForm()
                {
                    $('#connect').hide();
                    $('#home').show();
                }

                function updateClass(type)
                {
                    if (type === 'login') {
                        $('[data-toggle-class]').addClass('js-login-form').removeClass('js-register-form');
                    } else {
                        $('[data-toggle-class]').removeClass('js-login-form').addClass('js-register-form');
                    }
                }

                function updateTexts(type)
                {
                    $('[data-multitext]').each(
                        function () {
                            $(this).text($(this).data(type));
                        }
                    )
                }

                function decodeQueryString(query)
                {
                    var params = {}, tokens, regexp = /[?&]?([^=]+)=([^&]*)/g;
                    query = query.split('+').join(' ');
                    while (tokens = regexp.exec(query)) {
                        params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]);
                    }
                    return params;
                }

                function getLink(action)
                {
                    return '?slaction=' + action + '&form_key=' + FORM_KEY ;
                }

                var $content = $('#content'), $document = $(document);

                $document.on(
                    'click',
                    '.js-login',
                    function () {
                        openForm('login');
                    }
                ).on(
                    'click',
                    '.js-register',
                    function () {
                        openForm('register');
                    }
                ).on(
                    'click',
                    '[data-toggle-form]',
                    function () {
                        if (lastOpen === 'login') {
                            openForm('register');
                        } else {
                            openForm('login');
                        }
                    }
                ).on(
                    'click',
                    '.js-close-form',
                    function () {
                        closeForm();
                    }
                ).on(
                    'click',
                    '.js-action-disable',
                    function () {
                        $content.load(getLink('disable') + ' #content');
                    }
                ).on(
                    'submit',
                    '.js-login-form',
                    function (event) {
                        event.preventDefault();
                        var $loader = $(this).find('.loader'), $button = $(this).find('button'), $loginForm = $('.js-login-form');
                        $button.hide();
                        $loader.show();
                        $content.load(
                            getLink('login') + ' #content',
                            {
                                email: $loginForm.find('input[name="email"]').val(),
                                password: $loginForm.find('input[name="password"]').val()
                            },
                            function () {
                                $loader.hide();
                                $button.show();
                            }
                        );
                    }
                ).on(
                    'submit',
                    '.js-register-form',
                    function (event) {
                        event.preventDefault();
                        var $loader = $(this).find('.loader'), $button = $(this).find('button'), $registerForm = $('.js-register-form');
                        $button.hide();
                        $loader.show();
                        $content.load(
                            getLink('register') + ' #content',
                            {
                                email: $registerForm.find('input[name="email"]').val(),
                                password: $registerForm.find('input[name="password"]').val()
                            },
                            function () {
                                $loader.hide();
                                $button.show();
                            }
                        );
                    }
                ).on(
                    'submit',
                    '.js-project-form',
                    function (event) {
                        event.preventDefault();
                        var $loader = $(this).find('.loader'), $button = $(this).find('button'), $codeForm = $('.js-project-form');
                        var project = $codeForm.find('select[name="project"]').val();
                        console.log(project);
                        if (!project) {
                            project = $codeForm.find('input[name="projectName"]').val();
                            if (!project) {
                                return;
                            } else {
                                project = '_' + project;
                            }
                        }
                        $button.hide();
                        $loader.show();

                        $content.load(
                            getLink('update') + ' #content',
                            {
                                project: project
                            },
                            function () {
                                $loader.hide();
                                $button.show();
                            }
                        );
                    }
                ).on(
                    'change',
                    '.js-project-select',
                    function () {
                        if (!$(this).val()) {
                            $('.js-new-project').show();
                        } else {
                            $('.js-new-project').hide();
                        }
                    }
                );
            }
        );
    }
);
