<?php

namespace Drupal\Tests\home_redirect_lang\Functional;

/**
 * Asserts the redirections for tests.
 */
trait AssertRedirectTrait {

  /**
   * Visits a path and asserts that it is a redirect.
   *
   * @param string $path
   *   The request path.
   * @param string $expected_destination
   *   The path where we expect it to redirect. If NULL value provided, no
   *   redirect is expected.
   * @param int $status_code
   *   The status we expect to get with the first request.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function assertRedirect($path, $expected_destination, $status_code = 301): void {
    // Always just use getAbsolutePath() so that generating the link does not
    // alter special requests.
    $url = $this->getAbsoluteUrl($path);
    $this->getSession()->visit($url);

    // Ensure that any changes to variables in the other thread are picked up.
    $location = $this->getSession()->getResponseHeader('Location');
    $this->assertEquals($this->getAbsoluteUrl($expected_destination), $this->getAbsoluteUrl($location));
    $assert_session = $this->assertSession();
    $assert_session->statusCodeEquals($status_code);
  }

  /**
   * Visits a path and asserts that it is NOT a redirect.
   *
   * @param string $path
   *   The path to visit.
   * @param int $status_code
   *   (optional) The expected HTTP status code. Defaults to 200.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function assertNoRedirect($path, $status_code = 200): void {
    $url = $this->getAbsoluteUrl($path);
    $this->getSession()->visit($url);

    $assert_session = $this->assertSession();
    $assert_session->statusCodeEquals($status_code);
    $assert_session->responseHeaderEquals('Location', NULL);
    $assert_session->responseNotContains('http-equiv="refresh');
    $assert_session->addressEquals($path);

    // Ensure that any changes to variables in the other thread are picked up.
    $this->refreshVariables();
  }

}
