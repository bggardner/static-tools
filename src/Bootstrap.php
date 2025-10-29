<?php

namespace Bggardner\StaticTools;

class Bootstrap
{
    public static function alert(string $type, string $content, bool $dismissible = false): string
    {
        return '
<div class="alert alert-' . $type . ($dismissible ? ' alert-dismissible fade show' : '') . '">
  ' . $content . '
  ' . ($dismissible ? '<button type="button" class="btn-close" data-bs-dismiss="alert" arialabel="close"></button>' : '') . '
</div>';
    }

    public static function modal(string $title, string $body, ?string $footer, ?bool $static = true): string
    {
        ob_start();

        echo '
<div class="modal fade"' . ($static ? ' data-bs-backdrop="static"' : '') . ' tabindex="-1" aria-labelledby="modalTitle">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 id="modalTitle">' . $title . '</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
' . $body . '
      </div>';

        if (isset($footer)) {
            echo '
      <div class="modal-footer">
' . $footer . '
      </div>';
        }

        echo '
    </div>
  </div>
</div>';

        return ob_get_clean();
    }

    /**
     * @param int $count Total number of results
     * @param int $limit Number of results per page
     *                   Use of QueryString::get('limit') is not recommended unless range-limited.
     * @param int $page_limit Number of pages to display in page navigation
     * @param ?array $options If provided, array of integers to display as a limit drop-down.
     *                        User interaction must be handled externally.
     */
    public static function pagination(int $count, int $limit = 25, int $page_limit = 10, ?array $options = [25, 50, 100]): string
    {
        $page = intval(QueryString::get('page') ?? 1);
        $start = ($page - 1) * $limit + 1;

        if ($start > $count || $start <= 0) {
            $start = 1;
        }

        $first = min($count, $start);
        $last = min($count, $start + $limit - 1);
        $pages = ceil($count / $limit);
        $min_page = max(1, min($pages - $page_limit + 1, $page - floor($page_limit / 2)));
        $max_page = min($pages, max($page_limit, $page + floor($page_limit / 2) - 1));

        ob_start();

        echo '
<nav>
  <ul class="pagination mb-0 mt-2">
    <li class="page-item disabled text-nowrap">
      <div class="page-link">
        <strong>' . Miscellaneous::numberFormat($first) . ($first == $last ? '' : ' - ' . Miscellaneous::numberFormat($last)) . '</strong> of ' . Miscellaneous::numberFormat($count) . '
      </div>
    </li>';

        if ($count > $limit) {

            if ($min_page > 1) {
                echo '
    <li class="page-item">
      <a class="page-link" href="?' . QueryString::get()->merge(['page' => 1])->build() . '" title="First page">
        <i class="bi-chevron-bar-left"></i>
      </a>
    </li>';

                echo '
    <li class="page-item">
      <a class="page-link" href="?' . QueryString::get()->merge(['page' => max(1, $page - $page_limit)])->build() . '" title="Previous ' . Miscellaneous::numberFormat($page_limit) . ' pages">
        <i class="bi-chevron-double-left"></i>
      </a>
    </li>';
            }

            if ($start >= $limit) {
            echo '
    <li class="page-item">
      <a class="page-link" href="?' . QueryString::get()->merge(['page' => $page - 1])->build() . '" title="Previous page">
        <span aria-hidden="true"><i class="bi-chevron-left"></i>
        </span>
      </a>
    </li>';
            }

            for ($i = $min_page; $i <= $max_page; $i++) {
                $active = $i == $page ? ' active' : '';
               echo '
    <li class="page-item' . $active . '">
      <a class="page-link" href="?' . QueryString::get()->merge(['page' => $i])->build() . '" title="Page ' . Miscellaneous::numberFormat($i) . '">
        ' . Miscellaneous::numberFormat($i) . '
      </a>
    </li>';
            }

            if ($start <= ($count - $limit)) {
                echo '
    <li class="page-item">
      <a class="page-link" href="?' . QueryString::get()->merge(['page' => $page + 1])->build() . '" title="Next page">
        <span aria-hidden="true"><i class="bi-chevron-right"></i></span>
      </a>
    </li>';
            }
        }

        if ($max_page < $pages) {
            echo '
    <li class="page-item">
      <a class="page-link" href="?' . QueryString::get()->merge(['page' => min($pages, $page + $page_limit)])->build() . '" title="Next ' . Miscellaneous::numberFormat($page_limit) . ' pages">
        <i class="bi-chevron-double-right"></i>
      </a>
    </li>';
        }

        if ($page < $pages) {
            echo '
    <li class="page-item">
      <a class="page-link" href="?' . QueryString::get()->merge(['page' => min($pages, $page + $page_limit)])->build() . '" title="Last page">
        <i class="bi-chevron-bar-right"></i>
      </a>
    </li>';
        }

        if ($count > $limit && is_array($options)) {
            echo '
    <li class="page-item">
      <div class="page-link">
        <select class="form-select border-0 py-0 ps-0">';

            foreach ($options as $option) {
                echo '
          <option' . ($option == $limit ? ' selected' : '') . '>' . Miscellaneous::numberFormat($option) . '</option>';
            }

            echo '
        </select>
      </div>
    </li>';
    }

        echo '
  </ul>
</nav>';

        return ob_get_clean();
    }

    /**
     * Return a hyperlinked icon for multi-field sorting
     *
     * @param string $field Name of field to sort
     * @param array $fields Array of all fields in [$name => $direction] format.
     *                      Direction is either 'ASC' or 'DESC'.
     * @param string $type Either 'alpha' or 'numeric'
     */
    public static function sortLink(string $field, array $fields, string $type = 'alpha'): string
    {
        if (!array_key_exists($field, $fields)) {
            throw new \Exception('Sort key [' . $field . '] does not exist');
        }
        if (is_array(QueryString::get('sort'))) {
            $fields = (new ArrayObject(QueryString::get('sort')))->intersectKeys($fields)->coalesce($fields)->getArrayCopy();
        }
        if (count($fields)) {
            $sorts = [];
            $has_priority = array_key_first($fields) == $field;
            foreach ($fields as $name => $direction) {
                if ($field == $name) {
                    $icon = 'bi-sort-' . $type . '-';
                    if ($direction == 'DESC') {
                        $icon .= 'down-alt';
                    } else {
                        $icon .= 'down';
                    }
                    if ($has_priority) {
                        if ($direction == 'DESC') {
                            $direction = 'ASC';
                        } else {
                            $direction = 'DESC';
                        }
                    }
                    $sorts = array_merge([$name => $direction], $sorts);
                } else {
                    $sorts[$name] = $direction;
                }
            }
            $url = QueryString::get()->merge(['sort' => $sorts])->build();
            if ($has_priority) {
                $link_class = 'link-primary';
            } else {
                $link_class = 'link-secondary';
            }
            $link = '<a href="?' . $url . '" class="' . $link_class . ' ms-1"><i class="' . $icon . '"></i></a>';
        } else {
            $link = '';
        }
        return $link;
    }
}
