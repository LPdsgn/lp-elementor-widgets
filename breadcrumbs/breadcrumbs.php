<?php

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class Breadcrumbs extends \Elementor\Widget_Base {

    public function get_name() {
        return 'advanced-breadcrumbs';
    }

    public function get_title() {
        return esc_html__('Advanced Breadcrumbs', 'lp-widgets');
    }

    public function get_icon() {
        return 'eicon-navigation-horizontal';
    }

    public function get_categories() {
        return ['general'];
    }

    public function get_keywords() {
        return ['breadcrumbs', 'navigazione', 'navigation'];
    }

    public function get_style_depends() {
        return [''];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('Impostazioni', 'lp-widgets'),
            ]
        );

        $this->add_control(
            'separator',
            [
                'label' => __('Separator', 'lp-widgets'),
                'type' => Controls_Manager::TEXT,
                'default' => ';&raquo;', // Aggiungere spazi attorno al separatore
            ]
        );

        $this->add_control(
            'show_home',
            [
                'label' => __('Show Home', 'lp-widgets'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'lp-widgets'),
                'label_off' => __('Hide', 'lp-widgets'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_archive',
            [
                'label' => __('Show Archive', 'lp-widgets'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'lp-widgets'),
                'label_off' => __('Hide', 'lp-widgets'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_primary_category',
            [
                'label' => __('Show Primary Category', 'lp-widgets'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'lp-widgets'),
                'label_off' => __('Hide', 'lp-widgets'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_post_category',
            [
                'label' => __('Show Parent Category', 'lp-widgets'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'lp-widgets'),
                'label_off' => __('Hide', 'lp-widgets'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'max_child_categories',
            [
                'label' => __('Max Child Categories', 'lp-widgets'),
                'type' => Controls_Manager::NUMBER,
                'description' => __('Enter the maximum number of child categories to display.', 'lp-widgets'),
                'default' => 3,
            ]
        );

        $this->add_control(
            'show_current_post',
            [
                'label' => __('Show Current Post', 'lp-widgets'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'lp-widgets'),
                'label_off' => __('Hide', 'lp-widgets'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style',
            [
                'label' => __('Style', 'lp-widgets'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'color',
            [
                'label' => __('Text Color', 'lp-widgets'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .breadcrumbs' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .breadcrumbs a' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .breadcrumbs .separator' => 'margin: 0 5px;', // CSS per distanziare il separatore
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'typography',
                'label' => __('Typography', 'lp-widgets'),
                'selector' => '{{WRAPPER}} .breadcrumbs, {{WRAPPER}} .breadcrumbs a',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        // Get the settings
        $settings = $this->get_settings_for_display();
        $separator = $settings['separator'];
        $show_home = $settings['show_home'] === 'yes';
        $show_archive = $settings['show_archive'] === 'yes';
        $show_primary_category = $settings['show_primary_category'] === 'yes';
        $show_current_post = $settings['show_current_post'] === 'yes';
        $show_post_category = $settings['show_post_category'] === 'yes';
        $max_child_categories = $settings['max_child_categories'];

        // Get the global post object
        global $post;

        // Ottenere automaticamente l'ID della pagina del blog
        $blog_page_id = get_option('page_for_posts');

        // Initialize the breadcrumbs array
        $breadcrumbs = [];

        // Home link
        if ($show_home) {
            $breadcrumbs[] = [
                'title' => __('Home', 'lp-widgets'),
                'url' => home_url('/')
            ];
        }

        // If it's a single post, add the post hierarchy
        if (is_single()) {

            // Get the post type
            $post_type = get_post_type($post);

            // Add the Blog page if set and post type is 'post'
            if ($show_archive && $post_type === 'post' && $blog_page_id) {
                $breadcrumbs[] = [
                    'title' => get_the_title($blog_page_id),
                    'url' => get_permalink($blog_page_id)
                ];
            }

            // Add the archive page for custom post types if it exists
            if ($show_archive && $post_type !== 'post') {
                $post_type_object = get_post_type_object($post_type);
                if ($post_type_object && !empty($post_type_object->has_archive)) {
                    $breadcrumbs[] = [
                        'title' => $post_type_object->labels->name,
                        'url' => get_post_type_archive_link($post_type)
                    ];
                }
            }

            // Handle categories for post and custom post types
            if ($show_post_category) {
                $taxonomy = ($post_type === 'post') ? 'category' : get_object_taxonomies($post_type, 'names')[0];
                $categories = get_the_terms($post->ID, $taxonomy);
                if (!empty($categories) && !is_wp_error($categories)) {
                    // Sort categories by hierarchy (parent-child relationship)
                    usort($categories, function($a, $b) {
                        return ($a->parent == $b->term_id) ? 1 : -1;
                    });

                    // Add the primary category if visibility is enabled
                    if ($show_primary_category) {
                        $primary_category = $categories[0];
                        if ($primary_category->parent) {
                            $parent = get_term($primary_category->parent, $taxonomy);
                            $breadcrumbs[] = [
                                'title' => $parent->name,
                                'url' => get_term_link($parent->term_id, $taxonomy)
                            ];
                        }
                        $breadcrumbs[] = [
                            'title' => $primary_category->name,
                            'url' => get_term_link($primary_category->term_id, $taxonomy)
                        ];
                    }

                    // Add secondary categories up to the limit
                    $child_categories_count = 0;
                    foreach ($categories as $category) {
                        if ($category->term_id != $primary_category->term_id) {
                            if ($child_categories_count < $max_child_categories) {
                                $breadcrumbs[] = [
                                    'title' => $category->name,
                                    'url' => get_term_link($category->term_id, $taxonomy)
                                ];
                                $child_categories_count++;
                            } else {
                                break;
                            }
                        }
                    }
                }
            }

            // Add the current post
            if ($show_current_post) {
                $breadcrumbs[] = [
                    'title' => get_the_title($post->ID),
                    'url' => ''
                ];
            }
        }

        // If it's a page, add the page hierarchy
        if (is_page() && $post->post_parent) {
            // Get the post hierarchy
            $parents = [];
            $parent_id = $post->post_parent;
            while ($parent_id) {
                $parent = get_post($parent_id);
                $parents[] = [
                    'title' => get_the_title($parent->ID),
                    'url' => get_permalink($parent->ID)
                ];
                $parent_id = $parent->post_parent;
            }
            // Reverse the order of the parents
            $parents = array_reverse($parents);
            $breadcrumbs = array_merge($breadcrumbs, $parents);
            // Add the current page
            $breadcrumbs[] = [
                'title' => get_the_title($post->ID),
                'url' => get_permalink($post->ID)
            ];
        }

        // If it's a category archive
        if (is_category()) {
            // Get the current category
            $category = get_queried_object();
            // Add the category hierarchy
            while ($category->parent) {
                $parent = get_category($category->parent);
                $breadcrumbs[] = [
                    'title' => $parent->name,
                    'url' => get_category_link($parent->term_id)
                ];
                $category = $parent;
            }
            // Add the current category
            $breadcrumbs[] = [
                'title' => $category->name,
                'url' => get_category_link($category->term_id)
            ];
        }

        // If it's a tag archive
        if (is_tag()) {
            // Get the current tag
            $tag = get_queried_object();
            $breadcrumbs[] = [
                'title' => $tag->name,
                'url' => get_tag_link($tag->term_id)
            ];
        }

        // If it's an author archive
        if (is_author()) {
            // Get the current author
            $author = get_queried_object();
            $breadcrumbs[] = [
                'title' => __('Author: ', 'lp-widgets') . $author->display_name,
                'url' => get_author_posts_url($author->ID)
            ];
        }

        // If it's a search results page
        if (is_search()) {
            $breadcrumbs[] = [
                'title' => __('Search results for: ', 'lp-widgets') . get_search_query(),
                'url' => ''
            ];
        }

        // If it's a 404 page
        if (is_404()) {
            $breadcrumbs[] = [
                'title' => __('404 Not Found', 'lp-widgets'),
                'url' => ''
            ];
        }

        // If it's a product page (WooCommerce)
        if (function_exists('is_woocommerce') && is_product()) {
            // Get the product categories
            $terms = wp_get_post_terms($post->ID, 'product_cat');
            if (!empty($terms)) {
                $term = $terms[0];
                // Add the category hierarchy
                while ($term->parent) {
                    $parent = get_term($term->parent, 'product_cat');
                    $breadcrumbs[] = [
                        'title' => $parent->name,
                        'url' => get_term_link($parent->term_id, 'product_cat')
                    ];
                    $term = $parent;
                }
                // Add the current category
                $breadcrumbs[] = [
                    'title' => $term->name,
                    'url' => get_term_link($term->term_id, 'product_cat')
                ];
            }
            // Add the current product
            if ($show_current_post) {
                $breadcrumbs[] = [
                    'title' => get_the_title($post->ID),
                    'url' => ''
                ];
            }
        }

        // Output the breadcrumbs
        echo '<nav class="breadcrumbs">';
        foreach ($breadcrumbs as $index => $breadcrumb) {
            if ($index > 0) {
                echo '&ensp;' . esc_html($separator) . '&ensp;';
            }
            if (!empty($breadcrumb['url'])) {
                echo '<a href="' . esc_url($breadcrumb['url']) . '">' . esc_html($breadcrumb['title']) . '</a>';
            } else {
                echo esc_html($breadcrumb['title']);
            }
        }
        echo '</nav>';
    }
}
