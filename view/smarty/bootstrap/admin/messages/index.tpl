{extends file="base/index"}

{block name="styles" append}
    {style src="bootstrap/css/repository.css"}
{/block}

{block name="scripts_inline" append}
<script type="text/javascript">
    $(function() {
        $(".btn-toggle-body").on('click', function(e) {
            e.preventDefault();

            $(this).parent().next().toggleClass('hidden');
        });
    });
</script>
{/block}

{block name="head_title" prepend}{translate key="title.activity"} | {/block}

{block name="content_title"}
    <div class="page-header">
        <h1>{translate key="title.activity"}</h1>
    </div>
{/block}

{block name="content" append}
    {if $messages}
        <table class="table table-responsive table-striped">
            <tbody>
        {foreach $messages as $message}
            {$type = $message->getType()}
            {if $type == 'error'}
                {$type = 'danger'}
            {/if}
                <tr>
                    <td class="action hidden-xs">
                        <span class="label label-{$type}">{$message->getType()}</span>
                    </td>
                    <td class="action text-center">
                        <span class="visible-xs label label-{$type}">{$message->getType()}</span>
                        <small>
                            {$message->getDateAdded()|date_format:"%Y/%m/%d %H:%M:%S"}
                        </small>
                    </td>
                    <td>
                        <a href="{url id="messages.detail" parameters=["id" => $message->getId()]}">
                            {$message->getTitle()}
                        </a>
                        <br>
                        <small>{$message->getDescription()|replace:" ":"&nbsp;"|nl2br}</small>
                    </td>
                    <td>
                        {if $message->getRepository()}
                        <a href="{url id="repositories.detail" parameters=["repository" => $message->getRepository()->getSlug()]}">
                            {$message->getRepository()->getName()}
                        </a>
                        {elseif $message->getProject()}
                        <a href="{url id="projects.detail" parameters=["project" => $message->getProject()->getCode()]}">
                            {$message->getProject()->getCode()}
                        </a>
                        {/if}
                    </td>
                </tr>
        {/foreach}
            </tbody>
        </table>
    {else}
        <p>{translate key="label.messages.none"}</p>
    {/if}
{/block}
