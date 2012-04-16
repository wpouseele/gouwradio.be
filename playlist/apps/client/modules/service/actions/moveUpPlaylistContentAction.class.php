<?php
#
# Move up Content in the user's playlist 
#
class moveUpPlaylistContentAction extends sfAction
{
  public function execute($request)
  {
  		//validate required fields
    	if ( !$request->getParameter( 'playlist_id' ) ) $this->forward404();

    	//move up playlist content
		Doctrine_Core::getTable('PlaylistFiles')->moveUpPlaylistFile( $request->getParameter( 'playlist_id' ), $request->getParameter( 'id' ) );
		return sfView::NONE;
  }
}