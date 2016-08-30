{extends file="base/index"}

{block name="head_title" prepend}{translate key="title.servers"} | {/block}

{block name="content_title"}
    <div class="page-header">
        <h1>{translate key="title.servers"}</h1>
    </div>
{/block}

{block name="content" append}
    <p>
      <a href="{url id="servers.add"}?referer={$app.url.request|urlencode}" class="btn btn-default">
        <span class="glyphicon glyphicon-plus"></span> {translate key="button.server.add"}
      </a>
    </p>

    {if $servers}
    <table class="table table-responsive table-striped">
        <thead>
            <tr>
                <th class="action"></th>
                <th>{translate key="label.server"}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
    {foreach $servers as $server}
            <tr>
                <td class="action">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="glyphicon glyphicon-cog"></span> <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="{url id="servers.test" parameters=["id" => $server->getId()]}?referer={$app.url.request|urlencode}">
                                    {translate key="button.test"}
                                </a>
                            </li>
                            <li role="separator" class="divider"></li>
                            <li>
                                <a href="{url id="servers.edit" parameters=["id" => $server->getId()]}?referer={$app.url.request|urlencode}">
                                    {translate key="button.edit"}
                                </a>
                            </li>
                            <li>
                                <a href="{url id="servers.delete" parameters=["id" => $server->getId()]}?referer={$app.url.request|urlencode}">
                                    {translate key="button.delete"}
                                </a>
                            </li>
                        </ul>
                    </div>
                </td>
                <td>
                    <a href="{url id="servers.edit" parameters=["id" => $server->getId()]}?referer={$app.url.request|urlencode}">
                        {$server->getName()}
                    </a>
                    <br>
                    <small class="text-muted">
                        {$server->getUsername()}@{$server->getHost()}:{$server->getPort()}
                    </small>
                    <br>
                    {if $server->isWeb()}
                        <span class="label label-primary">web</span>
                    {/if}
                    {if $server->isDatabase()}
                        <span class="label label-info">database</span>
                    {/if}
                    {if $server->isCron()}
                        <span class="label label-default">cron</span>
                    {/if}
                    {if $server->isVarnish()}
                        <span class="label label-primary">varnish</span>
                    {/if}
                    {if $server->getFingerprintError()}
                    <br>
                    <small class="text-mute">{$server->getFingerprintError()|nl2br}</small>
                    {/if}
                </td>
                <td class="hidden-xs">
                    {$server->getIpAddress()}
                </td>
                <td class="text-right">
                    {if $server->getFingerprint()}
                    <span class="label label-success">{translate key="label.ok"}</span>
                    {else}
                    <span class="label label-danger">{translate key="label.error"}</span>
                    {/if}
                </td>
            </tr>
    {/foreach}
        </tbody>
    </table>
    {else}
    <p>{translate key="label.servers.none"}</p>
    {/if}

    {if $publicKey}
    <h2>{translate key="label.key.public"}</h2>
    <p>{translate key="label.server.key.public"}</p>
    <pre>{$publicKey}</pre>
    <p>{translate key="label.server.key.public.command"}</p>
    <pre>
mkdir ~/.ssh;
echo "{$publicKey}" >> ~/.ssh/authorized_keys;
chmod 600 ~/.ssh/authorized_keys</pre>
    {/if}
{/block}
